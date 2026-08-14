<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\AdvertisementStoreRequest;
use App\Http\Requests\Merchant\AdvertisementUpdateRequest;
use App\Services\Merchant\MerchantProductService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Auth;
use Illuminate\Http\RedirectResponse;

class MerchantAdvertisementController extends Controller
{
    protected MerchantProductService $productService;

    public function __construct(MerchantProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of the advertisements.
     */
    public function index(Request $request): Factory|View|JsonResponse
    {
        $user = Auth::user();
        $cyclosUserId = $user->cyclos_user_id;

        if (!$cyclosUserId) {
            return $request->wantsJson()
                ? response()->json(['error' => 'Cyclos user ID not found'], 400)
                : view('merchant.pages.advertisements.index', ['advertisements' => [], 'error' => 'Cyclos user ID not found']);
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
                'kind'       => 'simple',
            ];

            $result = $this->productService->searchProducts($cyclosUserId, $searchTerm, $filters);
            $rawAdvertisements = $result['products'];
            $headers = $result['headers'];

            Log::info('Products (advertisements) fetched successfully', [
                'total_advertisements' => count($rawAdvertisements),
                'headers'        => $headers,
                'sample_advertisement' => count($rawAdvertisements) > 0 ? $rawAdvertisements[0] : null
            ]);

            $formattedAdvertisements = $this->productService->formatProducts($rawAdvertisements);

            // Extract pagination data from API response headers (lowercase keys)
            $totalCount = (int) ($headers['x-total-count'] ?? count($rawAdvertisements));
            $apiPageSize = (int) ($headers['x-page-size'] ?? $perPage);
            $apiCurrentPage = (int) ($headers['x-current-page'] ?? $cyclosPage);

            $currentLaravelPage = $apiCurrentPage + 1;

            $advertisements = new \Illuminate\Pagination\LengthAwarePaginator(
                $formattedAdvertisements,       // items for current page
                $totalCount,                  // total items across all pages
                $apiPageSize,                 // items per page
                $currentLaravelPage,          // current page (1‑based)
                [
                    'path'  => $request->url(),
                    'query' => $request->query(), // preserves search, filters, etc.
                ]
            );

            $advertisementsData = $advertisements->toArray();

            $advertisementsData['pagination_headers'] = [
                'x_current_page'   => $headers['x-current-page'] ?? null,
                'x_page_size'      => $headers['x-page-size'] ?? null,
                'x_total_count'    => $headers['x-total-count'] ?? null,
                'x_page_count'     => $headers['x-page-count'] ?? null,
                'x_has_next_page'  => $headers['x-has-next-page'] ?? null,
            ];

            return $request->wantsJson()
                ? response()->json($advertisementsData)
                : view('merchant.pages.advertisements.index', ['advertisements' => $advertisements]);
        } catch (\Exception $e) {
            Log::error('Error fetching advertisements from Cyclos', [
                'error'            => $e->getMessage(),
                'user_id'          => $user->id,
                'cyclos_user_id'   => $cyclosUserId,
                'trace'            => $e->getTraceAsString()
            ]);

            return $request->wantsJson()
                ? response()->json(['error' => 'Failed to fetch advertisements: ' . $e->getMessage()], 500)
                : view('merchant.pages.advertisements.index', [
                    'advertisements' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10, 1),
                    'error'    => 'Failed to fetch advertisements: ' . $e->getMessage()
                ]);
        }
    }

    /**
     * Show the form for creating a new advertisement.
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

        return view('merchant.pages.advertisements.create', compact('flattenedCategories', 'tradeTypes', 'currencies'));
    }

    /**
     * Store a newly created advertisement in Cyclos.
     */
    public function store(AdvertisementStoreRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $cyclosUserId = $user->cyclos_user_id;

        if (!$cyclosUserId) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Cyclos user ID not found');
        }

        try {
            $advertisementData = $request->validated();

            $image = $request->hasFile('image') ? $request->file('image') : null;

            $result = $this->productService->createProduct(
                $cyclosUserId,
                $advertisementData,
                $image,
                'simple'
            );

            if ($result['success']) {
                return redirect()->route('merchant.advertisements.index')
                    ->with('success', 'Advertisement created successfully');
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
                    ->with('error', $result['message'] ?? 'Error creating advertisement');
            }
        } catch (\Exception $e) {
            Log::error('Exception in advertisement store', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating advertisement: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified advertisement.
     */
    public function edit(Request $request, string $advertisementId)
    {
        $user = Auth::user();
        $cyclosUserId = $user->cyclos_user_id;

        if (!$cyclosUserId) {
            return redirect()->route('merchant.advertisement.index')
                ->with('error', 'Cyclos user ID not found');
        }

        try {
            $advertisement = $this->productService->getProduct($advertisementId);

            if (!$advertisement) {
                return redirect()->route('merchant.products.index')
                    ->with('error', 'Advertisement not found');
            }

            $formattedAdvertisement = $this->productService->formatProduct($advertisement);
            $categoriesData = $this->productService->getMarketplaceCategories();
            $flattenedCategories = $this->productService->flattenCategories($categoriesData);

            $tradeTypes = $this->productService->getTradeTypes();
            $currencies = $this->productService->getCurrencies();

            $selectedCategoryId = $this->getSelectedCategoryId($advertisement, $categoriesData);

            return view('merchant.pages.advertisements.edit', [
                'advertisement' => $formattedAdvertisement,
                'rawAdvertisement' => $advertisement,
                'flattenedCategories' => $flattenedCategories,
                'categoriesData' => $categoriesData,
                'tradeTypes' => $tradeTypes,
                'currencies' => $currencies,
                'selectedCategoryId' => $selectedCategoryId  // Pass just the ID
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching advertisement for edit', [
                'error' => $e->getMessage(),
                'advertisement_id' => $advertisementId
            ]);

            return redirect()->route('merchant.advertisements.index')
                ->with('error', 'Error fetching advertisement');
        }
    }

    /**
     * Update the specified advertisement in Cyclos.
     */
    public function update(AdvertisementUpdateRequest $request, string $advertisementId): RedirectResponse
    {
        $user = Auth::user();
        $cyclosUserId = $user->cyclos_user_id;

        if (!$cyclosUserId) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Cyclos user ID not found');
        }

        try {
            $advertisementData = $request->validated();

            $image = $request->hasFile('image') ? $request->file('image') : null;

            // Add trade_type if not present
            if (!isset($advertisementData['trade_type'])) {
                $advertisementData['trade_type'] = 'tradeTypeOffer';
            }

            $result = $this->productService->updateProduct(
                $cyclosUserId,
                $advertisementId,
                $advertisementData,
                'simple',
                $image
            );

            if ($result['success']) {
                return redirect()->route('merchant.advertisements.index')
                    ->with('success', 'Advertisement updated successfully');
            } else {
                return redirect()->back()
                    ->withInput()
                    ->with('error', $result['message'] ?? 'Error updating advertisement');
            }
        } catch (\Exception $e) {
            Log::error('Exception in advertisement update', [
                'error' => $e->getMessage(),
                'advertisement_id' => $advertisementId,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating advertisement: ' . $e->getMessage());
        }
    }


    /**
     * Remove the specified advertisement from Cyclos.
     */
    public function destroy(string $advertisementId): JsonResponse
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
            $result = $this->productService->deleteProduct($cyclosUserId, $advertisementId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Advertisement deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Error deleting advertisement'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Exception in advertisement delete', [
                'error' => $e->getMessage(),
                'advertisement_id' => $advertisementId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting advertisement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get selected category ID from advertisement
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
