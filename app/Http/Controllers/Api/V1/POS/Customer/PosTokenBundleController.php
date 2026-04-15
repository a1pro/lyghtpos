<?php

namespace App\Http\Controllers\Api\V1\POS\Customer;

use App\Http\Controllers\Controller;
use App\Services\Api\POS\Customer\PosTokenBundleService;

class PosTokenBundleController extends Controller
{
    protected $tokenBundleService;

    public function __construct(PosTokenBundleService $tokenBundleService)
    {
        $this->tokenBundleService = $tokenBundleService;
    }

    public function getActiveTokenBundles()
    {
        try {
            $data = $this->tokenBundleService->fetchActiveTokenBundles();

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Active Bundles retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'An error occurred while processing your request' . $e->getMessage()
            ]);
        }
    }
}
