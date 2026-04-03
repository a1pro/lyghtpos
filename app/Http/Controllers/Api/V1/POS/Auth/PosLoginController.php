<?php

namespace App\Http\Controllers\Api\V1\POS\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\POS\Auth\PosLoginRequest;
use App\Services\Api\POS\Auth\PosAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosLoginController extends Controller
{
    protected $posAuthService;

    public function __construct(PosAuthService $posAuthService)
    {
        $this->posAuthService = $posAuthService;
    }

    public function login(PosLoginRequest $request)
    {
        try {
            DB::beginTransaction();
            $result = $this->posAuthService->authenticate($request->validated());
            DB::commit();

            return response()->json(['success' => true, 'status_code' => 200, 'message' => 'Login successful', 'data' => $result]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'An error occurred while processing your request'.$e->getMessage(),
            ]);
        }
    }

    public function userProfile()
    {
        try {
            $data = $this->posAuthService->userProfile(auth()->user());

            return response()->json(['success' => true, 'status_code' => 200, 'message' => 'User profile retrieved successfully', 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'status_code' => 500, 'message' => 'An error occurred while processing your request'.$e->getMessage()]);
        }
    }

    public function logout(Request $request)
    {
        try {
            DB::beginTransaction();
            $result = $this->posAuthService->logout($request->user());
            DB::commit();

            return response()->json(['success' => true, 'status_code' => 200, 'message' => 'Logout successful', 'data' => $result]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['success' => false, 'status_code' => 500, 'message' => 'An error occurred while processing your request'.$e->getMessage()]);
        }
    }
}
