<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\ProductStoreRequest;
use App\Http\Requests\Merchant\ProductUpdateRequest;
use App\Services\Merchant\MerchantProductService;
use App\Services\Merchant\MerchantPlansConditionService;
use App\Models\MerchantProductCount;
use App\Models\UserSubscription;
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
    protected MerchantPlansConditionService $productPlanCondition;

    public function __construct(MerchantProductService $productService , MerchantPlansConditionService $productPlanCondition)
    {
        $this->productService = $productService;
        $this->productPlanCondition = $productPlanCondition;
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

            $laravelPage = (int) $request->input('page', 1);
            $cyclosPage = max(0, $laravelPage - 1);
            $perPage = (int) $request->input('per_page', 10);

            $filters = [
                'category'   => $request->input('category'),
                'min_price'  => $request->input('min_price'),
                'max_price'  => $request->input('max_price'),
                'page'       => $cyclosPage,      // 0‑based for API
                'per_page'   => $perPage,
                'kind'       => 'webshop',
            ];

            $result = $this->productService->searchProducts($cyclosUserId, $searchTerm, $filters);
            $rawProducts = $result['products'];
            $headers = $result['headers'];

            Log::info('Products fetched successfully', [
                'total_products' => count($rawProducts),
                'headers'        => $headers,
                'sample_product' => count($rawProducts) > 0 ? $rawProducts[0] : null
            ]);

            $formattedProducts = $this->productService->formatProducts($rawProducts);

            // Extract pagination data from API response headers (lowercase keys)
            $totalCount = (int) ($headers['x-total-count'] ?? count($rawProducts));
            $apiPageSize = (int) ($headers['x-page-size'] ?? $perPage);
            $apiCurrentPage = (int) ($headers['x-current-page'] ?? $cyclosPage);

            $currentLaravelPage = $apiCurrentPage + 1;

            $products = new \Illuminate\Pagination\LengthAwarePaginator(
                $formattedProducts,           // items for current page
                $totalCount,                  // total items across all pages
                $apiPageSize,                 // items per page
                $currentLaravelPage,          // current page (1‑based)
                [
                    'path'  => $request->url(),
                    'query' => $request->query(), // preserves search, filters, etc.
                ]
            );

            $productsData = $products->toArray();

            $productsData['pagination_headers'] = [
                'x_current_page'   => $headers['x-current-page'] ?? null,
                'x_page_size'      => $headers['x-page-size'] ?? null,
                'x_total_count'    => $headers['x-total-count'] ?? null,
                'x_page_count'     => $headers['x-page-count'] ?? null,
                'x_has_next_page'  => $headers['x-has-next-page'] ?? null,
            ];

            return $request->wantsJson()
                ? response()->json($productsData)
                : view('merchant.pages.products.index', ['products' => $products]);
        } catch (\Exception $e) {
            Log::error('Error fetching products from Cyclos', [
                'error'            => $e->getMessage(),
                'user_id'          => $user->id,
                'cyclos_user_id'   => $cyclosUserId,
                'trace'            => $e->getTraceAsString()
            ]);

            return $request->wantsJson()
                ? response()->json(['error' => 'Failed to fetch products: ' . $e->getMessage()], 500)
                : view('merchant.pages.products.index', [
                    'products' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10, 1),
                    'error'    => 'Failed to fetch products: ' . $e->getMessage()
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
        // Check if user can add products BEFORE creating the product
        $canAddCheck = $this->productPlanCondition->canAddProducts($user->id);
        
        if (!$canAddCheck['can_add']) {
            return redirect()->back()
                ->withInput()
                ->with('error', $canAddCheck['message']);
        }
        
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
            // After successful product creation, record the product count
            try {
                $this->addProductCount($user->id, 1);
            } catch (\Exception $e) {
                // Log error but don't fail the product creation
                Log::error('Failed to record product count', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                
                // Optional: You might want to return error here if tracking is critical
                // return redirect()->back()->withInput()->with('error', 'Product created but failed to update count: ' . $e->getMessage());
            }

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

    protected function addProductCount($userId, $count)
{
    try {
        // Get user's active subscription
        $activeSubscription = UserSubscription::where('user_id', $userId)
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->latest()
            ->first();
        
        if (!$activeSubscription) {
            throw new \Exception('No active subscription found for this user');
        }
        
        // Check if user can add products
        $canAddCheck = $this->productPlanCondition->canAddProducts($userId, $count);
        
        if (!$canAddCheck['can_add']) {
            throw new \Exception($canAddCheck['message']);
        }
        
        // Create record of added products for this plan period
        $productCount = MerchantProductCount::create([
            'user_id' => $userId,
            'plan_id' => $activeSubscription->plan_id,
            'product_insert_count' => $count,
            'plan_start_date' => $activeSubscription->start_date,
            'plan_end_date' => $activeSubscription->end_date
        ]);
        
        return $productCount;
        
    } catch (\Exception $e) {
        Log::error('Error in addProductCount', [
            'user_id' => $userId,
            'count' => $count,
            'error' => $e->getMessage()
        ]);
        
        throw $e; // Re-throw to handle in the calling method
    }
}


public function getRemainingProductCount()
{
    $user = Auth::user();
    $canAddCheck = MerchantProductCount::canAddProducts($user->id);
    
    return $canAddCheck['remaining'] ?? 0;
}


    /**
     * Display the specified product.
     */
    public function show(Request $request, string $productId): Factory|View|JsonResponse|RedirectResponse
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
            $product = $this->productService->getProduct($productId);

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
            $product = $this->productService->getProduct($productId);

            if (!$product) {
                return redirect()->route('merchant.products.index')
                    ->with('error', 'Product not found');
            }

            $formattedProduct = $this->productService->formatProduct($product);
            $categoriesData = $this->productService->getMarketplaceCategories();
            $flattenedCategories = $this->productService->flattenCategories($categoriesData);

            $tradeTypes = $this->productService->getTradeTypes();
            $currencies = $this->productService->getCurrencies();

            $selectedCategoryId = $this->getSelectedCategoryId($product, $categoriesData);

            return view('merchant.pages.products.edit', [
                'product' => $formattedProduct,
                'rawProduct' => $product,
                'flattenedCategories' => $flattenedCategories,
                'categoriesData' => $categoriesData,
                'tradeTypes' => $tradeTypes,
                'currencies' => $currencies,
                'selectedCategoryId' => $selectedCategoryId  // Pass just the ID
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

            $result = $this->productService->updateProduct(
                $cyclosUserId,
                $productId,
                $productData
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
                return response()->json([
                    'success' => true,
                    'message' => 'Product deleted successfully'
                ]);
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

    /**
     * Get selected category ID from product
     */
    private function getSelectedCategoryId($rawProduct, $categoriesData): ?string
    {
        if (empty($rawProduct['categories']) || !is_array($rawProduct['categories'])) {
            return null;
        }

        $firstCategory = $rawProduct['categories'][0];

        // Get the internal name (could be string or array)
        $internalName = null;
        if (is_array($firstCategory) && isset($firstCategory['internalName'])) {
            $internalName = $firstCategory['internalName'];
        } elseif (is_string($firstCategory)) {
            $internalName = $firstCategory;
        }

        if (!$internalName) {
            return null;
        }

        return $this->productService->findCategoryIdByInternalName($categoriesData, $internalName);
    }
}
