<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\ProductStoreRequest;
use App\Http\Requests\Merchant\ProductUpdateRequest;
use App\Services\Merchant\MerchantProductService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Auth;

class MerchantProductController extends Controller
{
    protected MerchantProductService $productService;

    public function __construct(MerchantProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of the products.
     */
    public function index(Request $request): Factory|View|JsonResponse
    {
        $user = Auth::user();
        $cyclosUserId = $user->cyclos_user_id;
        
        if (!$cyclosUserId) {
            return $request->wantsJson()
                ? response()->json(['error' => 'Cyclos user ID not found'], 400)
                : view('merchant.pages.products.index', ['products' => [], 'error' => 'Cyclos user ID not found']);
        }

        try {
            $searchTerm = $request->input('search', '');
            $filters = [
                'category' => $request->input('category'),
                'min_price' => $request->input('min_price'),
                'max_price' => $request->input('max_price'),
                'page' => $request->input('page', 0),
                'per_page' => $request->input('per_page', 10),
                'kind' => 'webshop',
            ];

            $result = $this->productService->searchProducts($cyclosUserId, $searchTerm, $filters);
            $rawProducts = $result['products'];
            $headers = $result['headers'];
            $formattedProducts = $this->productService->formatProducts($rawProducts);
            
            $totalCount = count($rawProducts);
            $currentPage = $request->input('page', 1);
            $perPage = $request->input('per_page', 4);
            
            $products = new \Illuminate\Pagination\LengthAwarePaginator(
                $formattedProducts,
                $totalCount,
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            Log::info('Productsssss==========', [
                'headers' => $headers,
            ]);

            $productsData = $products->toArray();
            $productsData['pagination_headers'] = [
                'x_current_page' => $headers['x-current-page'] ?? null,
                'x_page_size' => $headers['x-page-size'] ?? null,
                'x_total_count' => $headers['x-total-count'] ?? null,
                'x_page_count' => $headers['x-page-count'] ?? null,
                'x_has_next_page' => $headers['x-has-next-page'] ?? null,
            ];


            return $request->wantsJson()
                ? response()->json($productsData)
                : view('merchant.pages.products.index', ['products' => $products]);
                
        } catch (\Exception $e) {
            Log::error('Error fetching products from Cyclos', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'cyclos_user_id' => $cyclosUserId
            ]);

            return $request->wantsJson()
                ? response()->json(['error' => 'Failed to fetch products'], 500)
                : view('merchant.pages.products.index', [
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
        $user = Auth::user();
        $cyclosUserId = $user->cyclos_user_id;
        
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
                'sample_with_ids' => $sample,
                'first_key' => array_key_first($flattenedCategories),
                'first_key_type' => gettype(array_key_first($flattenedCategories)),
                'first_key_length' => strlen(array_key_first($flattenedCategories) ?? '')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch categories', ['error' => $e->getMessage()]);
        }
        
        return view('merchant.pages.products.create', compact('flattenedCategories', 'tradeTypes', 'currencies'));
    }

    /**
     * Store a newly created product in Cyclos.
     */
    public function store(ProductStoreRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $cyclosUserId = $user->cyclos_user_id;
        
        if (!$cyclosUserId) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Cyclos user ID not found');
        }

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
                return redirect()->route('merchant.products.index')
                    ->with('success', 'Product created successfully');
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
        $user = Auth::user();
        $cyclosUserId = $user->cyclos_user_id;
        
        if (!$cyclosUserId) {
            return $request->wantsJson()
                ? response()->json(['error' => 'Cyclos user ID not found'], 400)
                : redirect()->route('merchant.products.index')
                    ->with('error', 'Cyclos user ID not found');
        }

        try {
            $product = $this->productService->getProduct($cyclosUserId, $productId);
            
            if (!$product) {
                return $request->wantsJson()
                    ? response()->json(['error' => 'Product not found'], 404)
                    : redirect()->route('merchant.products.index')
                        ->with('error', 'Product not found');
            }

            $formattedProduct = $this->productService->formatProduct($product);

            return $request->wantsJson()
                ? response()->json($formattedProduct)
                : view('merchant.pages.products.show', ['product' => $formattedProduct]);
                
        } catch (\Exception $e) {
            Log::error('Error fetching product', [
                'error' => $e->getMessage(),
                'product_id' => $productId
            ]);

            return $request->wantsJson()
                ? response()->json(['error' => 'Failed to fetch product'], 500)
                : redirect()->route('merchant.products.index')
                    ->with('error', 'Error fetching product');
        }
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Request $request, string $productId)
    {
        $user = Auth::user();
        $cyclosUserId = $user->cyclos_user_id;
        
        if (!$cyclosUserId) {
            return redirect()->route('merchant.products.index')
                ->with('error', 'Cyclos user ID not found');
        }

        try {
            $product = $this->productService->getProduct($cyclosUserId, $productId);
            
            if (!$product) {
                return redirect()->route('merchant.products.index')
                    ->with('error', 'Product not found');
            }

            $formattedProduct = $this->productService->formatProduct($product);
            
            $categoriesData = $this->productService->getMarketplaceCategories();
            $flattenedCategories = $this->productService->flattenCategories($categoriesData);
            $tradeTypes = $this->productService->getTradeTypes();
            $currencies = $this->productService->getCurrencies();

            return view('merchant.pages.products.edit', [
                'product' => $formattedProduct,
                'rawProduct' => $product,
                'flattenedCategories' => $flattenedCategories,
                'tradeTypes' => $tradeTypes,
                'currencies' => $currencies
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching product for edit', [
                'error' => $e->getMessage(),
                'product_id' => $productId
            ]);

            return redirect()->route('merchant.products.index')
                ->with('error', 'Error fetching product');
        }
    }

    /**
     * Update the specified product in Cyclos.
     */
    public function update(ProductUpdateRequest $request, string $productId): RedirectResponse
    {
        $user = Auth::user();
        $cyclosUserId = $user->cyclos_user_id;
        
        if (!$cyclosUserId) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Cyclos user ID not found');
        }

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
                return redirect()->route('merchant.products.index')
                    ->with('success', 'Product updated successfully');
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
        $user = Auth::user();
        $cyclosUserId = $user->cyclos_user_id;
        
        if (!$cyclosUserId) {
            return response()->json([
                'success' => false,
                'message' => 'Cyclos user ID not found'
            ], 400);
        }

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
        $user = Auth::user();
        $cyclosUserId = $user->cyclos_user_id;
        
        if (!$cyclosUserId) {
            return response()->json([
                'success' => false,
                'message' => 'Cyclos user ID not found'
            ], 400);
        }

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