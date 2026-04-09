<?php

namespace App\Http\Controllers\Api\V1\POS\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Api\POS\Auth\PosRegisterRequest;
use App\Http\Requests\Api\POS\Auth\PosOtpVerificationRequest;
use App\Mail\POS\Customer\CustomerRegisterMail;
use App\Models\User;
use App\Services\Api\POS\Auth\PosAuthService;
use App\Services\Cyclos\CyclosUserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Auth;


class PosRegisterController extends Controller
{
    protected $posAuthService;

 

    public function __construct(PosAuthService $posAuthService)
    {
        $this->posAuthService = $posAuthService;
        
    }

    public function register(PosRegisterRequest $request)
    {
        try {
            DB::beginTransaction();
    
            // Register user locally
            $result = $this->posAuthService->register($request->validated());
    
            // Get the created user from result
            $user = $result['user'] ?? null;
    
            if (! $user) {
                throw new \Exception('User creation failed');
            }
    
            DB::commit();
    
            // Send Mail with OTP
            try {
                Mail::to($user->email)->send(new CustomerRegisterMail($user, $result['otp'], $result['validity']));
                Log::info('Welcome email with OTP sent to POS Customer', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'otp' => $result['otp'] // Remove this in production, only for debugging
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to send welcome email to POS customer', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }
    
            return response()->json([
                'success' => true,
                'status_code' => 201,
                'message' => 'Registration successful. Please check your email for OTP verification.',
                'data' => [
                    'user' => $user,
                    'token' => $result['token'],
                    // Don't send OTP in response in production
                    // 'otp' => $result['otp'] // Only for testing
                ],
            ], 201);
    
        } catch (\Exception $e) {
            DB::rollback();
    
            Log::error('POS Registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except('password'),
            ]);
    
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'An error occurred while processing your request: '.$e->getMessage(),
            ], 500);
        }
    }
    // ======================Register OTP Verification==========================
    public function optVerification(PosOtpVerificationRequest $request)
    {
        try {
            DB::beginTransaction();
            
            $verification = $this->posAuthService->otpVerify($request->validated());
            
            // Check if verification was successful
            if (!$verification['success']) {
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'status_code' => 400,
                    'message' => $verification['message']
                ]);
            }
            
            DB::commit();
            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'OTP Verified Successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ]);
        }
    }


    public function resendOtp(Request $request)
    {
        try{
            DB::beginTransaction();
            $userId = Auth::id();

            $newOtp = $this->posAuthService->resendOtp($userId);

            
            DB::commit();
            return response()->json([
                'success' => true,
                'status_code'=>200,
                'message' => 'Otp resend Successfully',
                'otp' => $newOtp['otp']
               
            ]);
        }
        catch(\Exception $e){
            DB::rollback();
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Something went wrong' .$e->getMessage(),
            ]);
        }
    }

    /**
     * Create user in Cyclos system
     */
   
}
