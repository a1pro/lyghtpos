<?php

namespace App\Http\Controllers\Api\V1\PAX\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PAX\Merchant\PaxProductUpdateRequest;
use App\Services\Cyclos\CyclosProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Carefull [CONTROLLER LINKED TO PAX/POS ROUTES]
 */
class PaxProductsController extends Controller
{
    protected CyclosProductService $productService;

    public function __construct(CyclosProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Get all product for authenticated merchant
     */
    public function getMerchantProducts(Request $request)
    {
        try {
            $merchantUser = Auth::user();

            if (!$merchantUser) {
                throw new \Exception("User not found.");
            }

            if (!$merchantUser->cyclos_user_id) {
                throw new \Exception("Cyclos ID not found.");
            }

            $searchTerm = $request->input('search', '');

            $page = (int) $request->input('page', 1);
            $cyclosPage = max(0, $page - 1);
            $perPage = (int) $request->input('per_page', 10);

            $filters = [
                'category'   => $request->input('category'),
                'page'       => $cyclosPage,
                'per_page'   => $perPage,
                'kind'       => 'webshop',
            ];

            $data = $this->productService->searchProducts($merchantUser->cyclos_user_id, $searchTerm, $filters);

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Products retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'An error occurred while processing your request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all categories for authenticated merchant
     */
    public function getCategories(Request $request)
    {
        try {
            $merchantUser = Auth::user();

            if (!$merchantUser) {
                throw new \Exception("User not found.");
            }

            if (!$merchantUser->cyclos_user_id) {
                throw new \Exception("Cyclos ID not found.");
            }

            $data = $this->productService->getMinimalCategories($merchantUser->cyclos_user_id);

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Product categories retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'An error occurred while processing your request: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateMerchantProduct(PaxProductUpdateRequest $request)
    {
        try {
            $merchantUser = Auth::user();

            if (!$merchantUser) {
                throw new \Exception("User not found.");
            }

            if (!$merchantUser->cyclos_user_id) {
                throw new \Exception("Cyclos ID not found.");
            }

            $productData = $request->validated();

            $productId = $productData['product_id'] ?? null;

            $image = $request->hasFile('image') ? $request->file('image') : null;

            $result = $this->productService->updateProduct(
                $merchantUser->cyclos_user_id,
                $productId,
                $productData,
                'webshop',
                $image
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'status_code' => 200,
                    'message' => 'Product updated successfully',
                    'image_uploaded' => $result['image_uploaded'] ?? null
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'status_code' => 400,
                    'message' => 'Failed to update product: ' . ($result['message'] ?? 'Unknown error')
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'An error occurred while processing your request: ' . $e->getMessage()
            ], 500);
        }
    }
}
