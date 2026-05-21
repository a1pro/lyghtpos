<?php

namespace App\Http\Controllers\Api\V1\PAX\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Api\PAX\Auth\PaxAuthService;
use App\Http\Requests\Api\PAX\Auth\PaxLoginRequest;
use Illuminate\Support\Facades\DB;

class PaxAuthController extends Controller
{
	protected PaxAuthService $authService;

	public function __construct(PaxAuthService $authService)
	{
		$this->authService = $authService;
	}

	public function login(PaxLoginRequest $request)
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
