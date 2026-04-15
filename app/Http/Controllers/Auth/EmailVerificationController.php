<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\POS\Customer\CustomerRegisterMail;
use App\Mail\POS\Customer\CustomerResendOtpMail;
use App\Mail\POS\Customer\CustomerVerificationSuccessMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class EmailVerificationController extends Controller
{
    /**
     * Show the email verification page
     */
    public function showVerificationForm()
    {
        $user = Auth::user();

        if ($user && !is_null($user->email_verified_at)) {
            return redirect()->route($this->getDashboardRoute());
        }

        return view('auth.verify-email', [
            'user' => $user,
            'redirectRoute' => $this->getDashboardRoute()
        ]);
    }

    /**
     * Send OTP to user's email (Initial send)
     */
    public function sendOtp(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            if (!is_null($user->email_verified_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already verified'
                ], 400);
            }

            if ($user->otp && $user->time_validity && Carbon::now()->lt($user->time_validity)) {
                return response()->json([
                    'success' => false,
                    'message' => "Verification email already been sent."
                ], 429);
            }

            // Generate 6-digit OTP
            $otp = random_int(100000, 999999);
            $validity = Carbon::now()->addMinutes(15);

            // Store OTP in database
            $user->otp = $otp;
            $user->time_validity = $validity;
            $user->save();

            // Send email with OTP
            try {
                Mail::to($user->email)->send(new CustomerRegisterMail($user, $otp, $validity));
                Log::info('Verification OTP sent to user', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'role' => $this->getUserRole($user),
                    'expires_at' => $validity->toDateTimeString()
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send verification email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                throw new \Exception('Failed to send verification email');
            }

            return response()->json([
                'success' => true,
                'message' => 'Verification code sent to your email',
                'expires_in' => 15,
                'expires_at' => $validity->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resend OTP to user's email
     */
    public function resendOtp(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            if (!is_null($user->email_verified_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already verified'
                ], 400);
            }

            // Generate new 6-digit OTP
            $otp = random_int(100000, 999999);
            $validity = Carbon::now()->addMinutes(15);

            // Update OTP in database
            $user->otp = $otp;
            $user->time_validity = $validity;
            $user->save();

            // Send email with OTP using CustomerResendOtpMail
            try {
                Mail::to($user->email)->send(new CustomerResendOtpMail($user, $otp, $validity));
                Log::info('Verification OTP resent to user', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'role' => $this->getUserRole($user),
                    'expires_at' => $validity->toDateTimeString()
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to resend verification email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                throw new \Exception('Failed to resend verification email');
            }

            return response()->json([
                'success' => true,
                'message' => 'Verification code resent to your email',
                'expires_in' => 15,
                'expires_at' => $validity->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend verification code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify OTP and mark email as verified
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6'
        ]);

        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            if (!is_null($user->email_verified_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already verified'
                ], 400);
            }

            // Check if OTP exists in database
            if (!$user->otp) {
                return response()->json([
                    'success' => false,
                    'message' => 'No OTP found. Please request a verification code.'
                ], 400);
            }

            // Check if OTP is expired
            if ($user->time_validity && Carbon::now()->gt($user->time_validity)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verification code has expired. Please request a new one.'
                ], 400);
            }

            // Check if OTP matches
            if ($request->otp != $user->otp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid verification code. Please try again.'
                ], 400);
            }

            // Update user's email_verified_at field and clear OTP
            $user->email_verified_at = Carbon::now();
            $user->email_verified = 'yes';
            $user->otp = null;
            $user->time_validity = null;
            $user->save();

            // Send success email
            try {
                Mail::to($user->email)->send(new CustomerVerificationSuccessMail($user, now()));
                Log::info('Email verified successfully', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'role' => $this->getUserRole($user)
                ]);
            } catch (\Exception $e) {
                // Don't throw error if success email fails, just log it
                Log::warning('Failed to send verification success email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }

            $intendedUrl = session()->pull('url.intended', route($this->getDashboardRoute()));

            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully!',
                'redirect_url' => $intendedUrl
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check email verification status
     */
    public function checkStatus()
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'verified' => !is_null($user->email_verified_at),
            'email' => $user->email,
            'has_valid_otp' => ($user->otp && $user->time_validity && Carbon::now()->lt($user->time_validity))
        ]);
    }

    /**
     * Get dashboard route based on user role
     */
    private function getDashboardRoute()
    {
        $user = Auth::user();
        $role = $this->getUserRole($user);

        $roleDashboardMap = [
            'merchant' => 'merchant.dashboard',
            'trade_member' => 'trade-member.dashboard',
            'organization' => 'organization-admin.dashboard',
            'admin' => 'admin.dashboard',
        ];

        return $roleDashboardMap[$role] ?? 'home';
    }

    /**
     * Get user role as string
     */
    private function getUserRole($user)
    {
        // Check if role relationship exists
        if (method_exists($user, 'role') && $user->role) {
            return $user->role->name;
        }

        // Check if role_id exists
        if (isset($user->role_id)) {
            $roleMap = [
                1 => 'admin',
                2 => 'merchant',
                3 => 'subadmin',
                6 => 'member',
                7 => 'organization',
                8 => 'favor_member',
            ];
            return $roleMap[$user->role_id] ?? 'user';
        }

        // Check if role is a direct string field
        if (isset($user->role) && is_string($user->role)) {
            return $user->role;
        }

        return 'user';
    }
}
