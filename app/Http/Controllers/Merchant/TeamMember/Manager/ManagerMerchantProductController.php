<?php

namespace App\Http\Controllers\Merchant\TeamMember\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\ProductStoreRequest;
use App\Http\Requests\Merchant\ProductUpdateRequest;
use App\Services\Merchant\MerchantProductService;
use App\Models\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Auth;

class ManagerMerchantProductController extends Controller
{
    protected MerchantProductService $productService;

    public function __construct(MerchantProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Get the effective merchant user (main merchant or team member's parent merchant)
     */
    private function getEffectiveMerchant(): ?User
    {
        $user = Auth::user();
        
        // If user has merchant_id, they are a team member - get the main merchant
        if ($user->merchant_id) {
            $mainMerchant = User::find($user->merchant_id);
            
            // Check if the main merchant exists and has cyclos credentials
            if (!$mainMerchant || !$mainMerchant->cyclos_user_id) {
                Log::warning('Main merchant not found or missing cyclos credentials', [
                    'team_member_id' => $user->id,
                    'merchant_id' => $user->merchant_id
                ]);
                return null;
            }
            
            Log::info('Using main merchant credentials for team member', [
                'team_member_id' => $user->id,
                'team_member_role' => $user->role_id,
                'main_merchant_id' => $mainMerchant->id,
                'main_merchant_cyclos_id' => $mainMerchant->cyclos_user_id
            ]);
            
            return $mainMerchant;
        }
        
        // User is the main merchant (role 2) or other type
        if (!$user->cyclos_user_id) {
            Log::warning('Main merchant missing cyclos credentials', [
                'user_id' => $user->id,
                'role_id' => $user->role_id
            ]);
            return null;
        }
        
        return $user;
    }

    /**
     * Get the effective cyclos user ID
     */
    private function getEffectiveCyclosUserId(): ?string
    {
        $effectiveMerchant = $this->getEffectiveMerchant();
        return $effectiveMerchant?->cyclos_user_id;
    }

    /**
     * Display a listing of the products.
     */
    public function index(Request $request): Factory|View|JsonResponse
    {
        $effectiveMerchant = $this->getEffectiveMerchant();
        
        if (!$effectiveMerchant) {
            $error = 'Unable to determine merchant credentials';
            return $request->wantsJson()
                ? response()->json(['error' => $error], 400)
                : view('merchant.team-members.manager.pages.products.index', ['products' => [], 'error' => $error]);
        }

        $cyclosUserId = $effectiveMerchant->cyclos_user_id;

        try {
            $searchTerm = $request->input('search', '');
            $filters = [
                'category' => $request->input('category'),
                'min_price' => $request->input('min_price'),
                'max_price' => $request->input('max_price'),
                'page' => $request->input('page', 0),
                'per_page' => $request->input('per_page', 10),
            ];

            $rawProducts = $this->productService->searchProducts($cyclosUserId, $searchTerm, $filters);
            $formattedProducts = $this->productService->formatProducts($rawProducts);
            
            $totalCount = count($rawProducts);
            $currentPage = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);
            
            $products = new \Illuminate\Pagination\LengthAwarePaginator(
                $formattedProducts,
                $totalCount,
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            // Add merchant info to view for debugging/display
            $merchantInfo = [
                'is_team_member' => !is_null(Auth::user()->merchant_id),
                'main_merchant_name' => $effectiveMerchant->first_name . ' ' . $effectiveMerchant->last_name,
                'main_merchant_email' => $effectiveMerchant->email
            ];

            return $request->wantsJson()
                ? response()->json($products)
                : view('merchant.team-members.manager.pages.products.index', [
                    'products' => $products,
                    'merchantInfo' => $merchantInfo
                ]);
                
        } catch (\Exception $e) {
            Log::error('Error fetching products from Cyclos', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'effective_cyclos_user_id' => $cyclosUserId
            ]);

            return $request->wantsJson()
                ? response()->json(['error' => 'Failed to fetch products'], 500)
                : view('merchant.team-members.manager.pages.products.index', [
                    'products' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10, 1),
                    'error' => 'Failed to fetch products: ' . $e->getMessage()
                ]);
        }
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(Request $request): Factory|View
    {
        $effectiveMerchant = $this->getEffectiveMerchant();
        
        if (!$effectiveMerchant) {
            return redirect()->route('merchant.manager.products.index')
                ->with('error', 'Unable to determine merchant credentials');
        }
        
        $flattenedCategories = [];
        $tradeTypes = [];
        $currencies = [];
        
        try {
            $categoriesData = $this->productService->getMarketplaceCategories();
            $flattenedCategories = $this->productService->flattenCategories($categoriesData);
            $tradeTypes = $this->productService->getTradeTypes();
            $currencies = $this->productService->getCurrencies();
            
            $sample = [];
            $i = 0;
            foreach ($flattenedCategories as $id => $name) {
                if ($i < 5) {
                    $sample[$id] = $name;
                    $i++;
                } else {
                    break;
                }
            }
            
            Log::info('Flattened categories for dropdown', [
                'count' => count($flattenedCategories),
                'sample_with_ids' => $sample
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch categories', ['error' => $e->getMessage()]);
        }
        
        // Add merchant info to view
        $merchantInfo = [
            'is_team_member' => !is_null(Auth::user()->merchant_id),
            'main_merchant_name' => $effectiveMerchant->first_name . ' ' . $effectiveMerchant->last_name
        ];
        
        return view('merchant.team-members.manager.pages.products.create', compact(
            'flattenedCategories', 
            'tradeTypes', 
            'currencies',
            'merchantInfo'
        ));
    }

    /**
     * Store a newly created product in Cyclos.
     */
    public function store(ProductStoreRequest $request): RedirectResponse
    {
        $effectiveMerchant = $this->getEffectiveMerchant();
        
        if (!$effectiveMerchant) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Unable to determine merchant credentials');
        }

        $cyclosUserId = $effectiveMerchant->cyclos_user_id;

        try {
            $productData = $request->validated();
            
            // Add trade_type if not present (default to offer)
            if (!isset($productData['trade_type'])) {
                $productData['trade_type'] = 'tradeTypeOffer';
            }
            
            $image = $request->hasFile('image') ? $request->file('image') : null;

            $result = $this->productService->createProduct(
                $cyclosUserId,
                $productData,
                $image
            );

            if ($result['success']) {
                $message = Auth::user()->merchant_id 
                    ? 'Product created successfully for merchant: ' . $effectiveMerchant->first_name
                    : 'Product created successfully';
                
                return redirect()->route('merchant.manager.products.index')
                    ->with('success', $message);
            } else {
                if (isset($result['needs_category']) && $result['needs_category']) {
                    $categoriesData = $this->productService->getMarketplaceCategories();
                    $flattenedCategories = $this->productService->flattenCategories($categoriesData);
                    
                    return redirect()->back()
                        ->withInput()
                        ->with('error', $result['message'])
                        ->with('show_category_warning', true)
                        ->with('flattened_categories', $flattenedCategories);
                }
                
                return redirect()->back()
                    ->withInput()
                    ->with('error', $result['message'] ?? 'Error creating product');
            }
        } catch (\Exception $e) {
            Log::error('Exception in product store', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating product: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified product.
     */
    public function show(Request $request, string $productId): Factory|View|JsonResponse
    {
        $effectiveMerchant = $this->getEffectiveMerchant();
        
        if (!$effectiveMerchant) {
            return $request->wantsJson()
                ? response()->json(['error' => 'Unable to determine merchant credentials'], 400)
                : redirect()->route('merchant.manager.products.index')
                    ->with('error', 'Unable to determine merchant credentials');
        }

        $cyclosUserId = $effectiveMerchant->cyclos_user_id;

        try {
            $product = $this->productService->getProduct($cyclosUserId, $productId);
            
            if (!$product) {
                return $request->wantsJson()
                    ? response()->json(['error' => 'Product not found'], 404)
                    : redirect()->route('merchant.manager.products.index')
                        ->with('error', 'Product not found');
            }

            $formattedProduct = $this->productService->formatProduct($product);
            
            // Add merchant info
            $merchantInfo = [
                'is_team_member' => !is_null(Auth::user()->merchant_id),
                'main_merchant_name' => $effectiveMerchant->first_name . ' ' . $effectiveMerchant->last_name
            ];

            return $request->wantsJson()
                ? response()->json($formattedProduct)
                : view('merchant.team-members.manager.pages.products.show', [
                    'product' => $formattedProduct,
                    'merchantInfo' => $merchantInfo
                ]);
                
        } catch (\Exception $e) {
            Log::error('Error fetching product', [
                'error' => $e->getMessage(),
                'product_id' => $productId
            ]);

            return $request->wantsJson()
                ? response()->json(['error' => 'Failed to fetch product'], 500)
                : redirect()->route('merchant.manager.products.index')
                    ->with('error', 'Error fetching product');
        }
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Request $request, string $productId): Factory|View
    {
        $effectiveMerchant = $this->getEffectiveMerchant();
        
        if (!$effectiveMerchant) {
            return redirect()->route('merchant.manager.products.index')
                ->with('error', 'Unable to determine merchant credentials');
        }

        $cyclosUserId = $effectiveMerchant->cyclos_user_id;

        try {
            $product = $this->productService->getProduct($cyclosUserId, $productId);
            
            if (!$product) {
                return redirect()->route('merchant.manager.products.index')
                    ->with('error', 'Product not found');
            }

            $formattedProduct = $this->productService->formatProduct($product);
            
            $categoriesData = $this->productService->getMarketplaceCategories();
            $flattenedCategories = $this->productService->flattenCategories($categoriesData);
            $tradeTypes = $this->productService->getTradeTypes();
            $currencies = $this->productService->getCurrencies();
            
            $merchantInfo = [
                'is_team_member' => !is_null(Auth::user()->merchant_id),
                'main_merchant_name' => $effectiveMerchant->first_name . ' ' . $effectiveMerchant->last_name
            ];

            return view('merchant.team-members.manager.pages.products.edit', [
                'product' => $formattedProduct,
                'rawProduct' => $product,
                'flattenedCategories' => $flattenedCategories,
                'tradeTypes' => $tradeTypes,
                'currencies' => $currencies,
                'merchantInfo' => $merchantInfo
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching product for edit', [
                'error' => $e->getMessage(),
                'product_id' => $productId
            ]);

            return redirect()->route('merchant.manager.products.index')
                ->with('error', 'Error fetching product');
        }
    }

    /**
     * Update the specified product in Cyclos.
     */
    public function update(ProductUpdateRequest $request, string $productId): RedirectResponse
    {
        $effectiveMerchant = $this->getEffectiveMerchant();
        
        if (!$effectiveMerchant) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Unable to determine merchant credentials');
        }

        $cyclosUserId = $effectiveMerchant->cyclos_user_id;

        try {
            $productData = $request->validated();
            
            // Add trade_type if not present
            if (!isset($productData['trade_type'])) {
                $productData['trade_type'] = 'tradeTypeOffer';
            }
            
            $existingProduct = $this->productService->getProduct($cyclosUserId, $productId);
            $existingImageKey = $existingProduct['images'][0] ?? null;

            $image = $request->hasFile('image') ? $request->file('image') : null;

            $result = $this->productService->updateProduct(
                $cyclosUserId,
                $productId,
                $productData,
                $image,
                $existingImageKey
            );

            if ($result['success']) {
                $message = Auth::user()->merchant_id 
                    ? 'Product updated successfully for merchant: ' . $effectiveMerchant->first_name
                    : 'Product updated successfully';
                
                return redirect()->route('merchant.manager.products.index')
                    ->with('success', $message);
            } else {
                return redirect()->back()
                    ->withInput()
                    ->with('error', $result['message'] ?? 'Error updating product');
            }
        } catch (\Exception $e) {
            Log::error('Exception in product update', [
                'error' => $e->getMessage(),
                'product_id' => $productId,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating product: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified product from Cyclos.
     */
    public function destroy(string $productId): JsonResponse
    {
        $effectiveMerchant = $this->getEffectiveMerchant();
        
        if (!$effectiveMerchant) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to determine merchant credentials'
            ], 400);
        }

        $cyclosUserId = $effectiveMerchant->cyclos_user_id;

        try {
            $result = $this->productService->deleteProduct($cyclosUserId, $productId);

            if ($result['success']) {
                return response()->json(['success' => true]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Error deleting product'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Exception in product delete', [
                'error' => $e->getMessage(),
                'product_id' => $productId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search products (AJAX endpoint)
     */
    public function search(Request $request): JsonResponse
    {
        $effectiveMerchant = $this->getEffectiveMerchant();
        
        if (!$effectiveMerchant) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to determine merchant credentials'
            ], 400);
        }

        $cyclosUserId = $effectiveMerchant->cyclos_user_id;

        try {
            $searchTerm = $request->input('q', '');
            $filters = $request->input('filters', []);
            
            $rawProducts = $this->productService->searchProducts($cyclosUserId, $searchTerm, $filters);
            $formattedProducts = $this->productService->formatProducts($rawProducts);

            return response()->json([
                'success' => true,
                'data' => $formattedProducts
            ]);
        } catch (\Exception $e) {
            Log::error('Error searching products', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error searching products'
            ], 500);
        }
    }
}   