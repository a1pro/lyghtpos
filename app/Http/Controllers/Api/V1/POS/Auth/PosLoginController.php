<?php

namespace App\Http\Controllers\Api\V1\POS\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\POS\Auth\PosLoginRequest;
use App\Services\Api\POS\Auth\PosAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosLoginController extends Controller
{
    protected $posAuthService;

    /**
     * Inject POS authentication service dependency
     */
    public function __construct(PosAuthService $posAuthService)
    {
        $this->posAuthService = $posAuthService;
    }

    /**
     * Authenticate user and generate access token
     */
    public function login(PosLoginRequest $request)
    {
        try {
            DB::beginTransaction();

            $result = $this->posAuthService->authenticate($request->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Login successful',
                'data' => $result
            ], 200);
        } catch (ValidationException $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'status_code' => 422,
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'An error occurred while processing your request' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout user by revoking access token
     */
    public function logout(Request $request)
    {
        try {
            DB::beginTransaction();

            $result = $this->posAuthService->logout($request->user());

            DB::commit();

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Logout successful',
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'An error occurred while processing your request' . $e->getMessage()
            ], 500);
        }
    }
}
