<?php

namespace App\Http\Controllers\Api\V1\PAX\Merchant;

use App\Http\Controllers\Controller;
use App\Services\Api\PAX\Merchant\PaxCustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaxCustomersController extends Controller
{
    protected PaxCustomerService $customerService;

    public function __construct(PaxCustomerService $customerService)
    {
        $this->customerService = $customerService;
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
}
