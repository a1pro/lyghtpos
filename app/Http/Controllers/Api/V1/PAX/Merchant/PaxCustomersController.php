<?php

namespace App\Http\Controllers\Api\V1\PAX\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PAX\Merchant\PaxCustomerCreateRequest;
use App\Services\Api\PAX\Merchant\PaxCustomerService;
use App\Services\Merchant\MerchantCustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Carefull [CONTROLLER LINKED TO PAX/POS ROUTES]
 */
class PaxCustomersController extends Controller
{
    protected PaxCustomerService $customerService;
    protected MerchantCustomerService $merchantCustomerService;

    public function __construct(PaxCustomerService $customerService, MerchantCustomerService $merchantCustomerService)
    {
        $this->customerService = $customerService;
        $this->merchantCustomerService = $merchantCustomerService;
    }

    /**
     * Get all product for authenticated merchant
     */
    public function getMerchantCustomers(Request $request)
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

            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            $data = $this->customerService->fetchMerchantCustomers($merchantUser, $searchTerm, $perPage, $page);

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Customers retrieved successfully',
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
     * Create customer for authenticated merchant
     */
    public function createMerchantCustomer(PaxCustomerCreateRequest $request)
    {
        try {
            $merchantId = Auth::id();

            if (!$merchantId) {
                throw new \Exception("User not found.");
            }

            $data = $request->validated();

            $data['merchant_id'] = $merchantId;

            $this->merchantCustomerService->createPaxCustomer($data);

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Customers created successfully',
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
}
