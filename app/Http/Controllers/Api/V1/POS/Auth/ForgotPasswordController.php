<?php

namespace App\Http\Controllers\Api\V1\POS\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\POS\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\POS\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\POS\Auth\VerifyForgotPasswordOtpRequest;
use App\Services\Api\POS\Auth\PosAuthService;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    protected $posAuthService;

    public function __construct(PosAuthService $posAuthService)
    {
        $this->posAuthService = $posAuthService;
    }

    /**
     * Send OTP to given email
     */
    public function sendOtp(ForgotPasswordRequest $request)
    {
        try {
            $this->posAuthService->sendForgotPasswordOtp($request->validated());

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Otp send successfully',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'status_code' => 422,
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 422,
                'message' => 'An error occured: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify the OTP for forgot password
     */
    public function verifyOtp(VerifyForgotPasswordOtpRequest $request)
    {
        try {
            $this->posAuthService->verifyForgotPasswordOtp($request->validated());

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'OTP verified successfully',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'status_code' => 422,
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 422,
                'message' => 'An error occured: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset password
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $this->posAuthService->resetPassword($request->validated());

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Password reset successfully',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'status_code' => 422,
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status_code' => 422,
                'message' => 'An error occured: ' . $e->getMessage(),
            ], 500);
        }
    }
}
