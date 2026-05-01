<?php

namespace App\Http\Controllers\Api\V1\POS\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\POS\Customer\PosCustomerUpdateProfileRequest;
use App\Services\Api\POS\Auth\PosAuthService;

class PosProfileController extends Controller
{
    protected PosAuthService $posAuthService;

    /**
     * Inject POS authentication service dependency
     */
    public function __construct(PosAuthService $posAuthService)
    {
        $this->posAuthService = $posAuthService;
    }

    /**
     * Get authenticated user profile information
     */
    public function userProfile()
    {
        try {
            $data = $this->posAuthService->userProfile();

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'User profile retrieved successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'An error occurred while processing your request' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get authenticated user profile information
     */
    public function updateProfile(PosCustomerUpdateProfileRequest $request)
    {
        try {
            $data = $this->posAuthService->updateUserProfile($request->validated());

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'User profile updated successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'An error occurred while processing your request' . $e->getMessage()
            ], 500);
        }
    }
}
