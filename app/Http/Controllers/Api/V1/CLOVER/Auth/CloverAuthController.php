<?php

namespace App\Http\Controllers\Api\V1\CLOVER\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CLOVER\Auth\CloverLoginRequest;
use Illuminate\Http\Request;
use App\Services\Api\CLOVER\Auth\CloverAuthService;
use Illuminate\Support\Facades\DB;

class CloverAuthController extends Controller
{
	protected CloverAuthService $authService;

	public function __construct(CloverAuthService $authService)
	{
		$this->authService = $authService;
	}

	public function login(CloverLoginRequest $request)
	{
		try {
			DB::beginTransaction();

			$data = $this->authService->authenticate($request->validated());

			DB::commit();
			return response()->json([
				'success' => true,
				'status_code' => 200,
				'data' => $data
			]);
		} catch (\Exception $e) {
			DB::rollback();
			return response()->json([
				'success' => false,
				'status_code' => 500,
				'message' => 'Unable to get Login - ' . $e->getMessage()
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

			$result = $this->authService->logout($request->user());

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
