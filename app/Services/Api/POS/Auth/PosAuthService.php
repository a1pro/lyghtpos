<?php

namespace App\Services\Api\POS\Auth;

use App\Models\Customer;
use App\Models\User;
use App\Models\Merchant;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Auth;
use Illuminate\Support\Facades\Log;

class PosAuthService
{
    /**
     * Authenticate POS user
     *
     * @param  array  $credentials
     *
     * @throws ValidationException
     */
    public function register(array $data): array
    {
        if (User::where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages(['email' => 'Email is already taken']);
        }

        if (User::where('phone', $data['phone'])->exists()) {
            throw ValidationException::withMessages(['phone' => 'Phone number is already taken']);
        }

        $referalCode = $data['referal_code'] ?? null;
        $merchantCode = null;

        if (!empty($referalCode)) {
            $merchantReferal = Merchant::where('merchant_id', $referalCode)->exists();

            if (!$merchantReferal) {
                throw new \Exception('Invalid referral code');
            }
            $merchantCode = Merchant::where('merchant_id', $referalCode)->value('user_id');
        }

        $otp = random_int(100000, 999999);
        $validity = Carbon::now()->addMinutes(15);
        $validityString = $validity->toDateTimeString();

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'location' => $data['location'],
            'role_id' => 11,
            'password' => Hash::make($data['password']),
            'merchant_id' => $merchantCode,
            'otp' => $otp,
            'time_validity' => $validity
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => $user->phone,
            'email' => $user->email,
            'address' => $user->location,
        ]);

        $token = $user->createToken('pos_auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'otp' => $otp,
            'validity' => $validityString,
            'message' => 'Registration successful',
        ];
    }

    // ================Otp Verify API===========================
    public function otpVerify(array $data)
    {
        try {
            $userId = Auth::id();

            // ==================Api Request Data=====================
            $enteredOtp = $data['otp'];
            $currentTime = Carbon::now();

            // =================DB Storage Data=======================
            $userOtp = User::where('id', $userId)->select('otp', 'time_validity', 'email_verified_at')->first();

            if (!$userOtp) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }

            $emailVerifiedAt = $userOtp->email_verified_at;
            if ($emailVerifiedAt) {
                return [
                    'success' => false,
                    'message' => 'OTP has already been verified'
                ];
            }

            $storedOtp = $userOtp->otp;
            $timeValidity = $userOtp->time_validity;

            // Check if OTP is expired
            if ($currentTime->gt($timeValidity)) {
                return [
                    'success' => false,
                    'message' => 'OTP has expired'
                ];
            }

            // Check if OTP matches
            if ($storedOtp != $enteredOtp) {
                return [
                    'success' => false,
                    'message' => 'Invalid OTP'
                ];
            }

            // OTP is valid - Update user
            User::where('id', $userId)->update([
                'email_verified' => 'yes',
                'email_verified_at' => $currentTime,
                'otp' => NULL,
                'time_validity' => NULL
            ]);

            return [
                'success' => true,
                'message' => 'OTP Verified Successfully'
            ];
        } catch (\Exception $e) {
            Log::error('OTP verification failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Verification error: ' . $e->getMessage()
            ];
        }
    }

    // ================OTP Resend Service========================

    public function resendOtp()
    {
        try {
            $userId = Auth::id();
            $otp = random_int(100000, 999999);

            $validity = Carbon::now()->addMinutes(15);

            $resendotp = User::where('id', $userId)->update([
                'otp' => $otp,
                'time_validity' => $validity
            ]);

            return [
                'success' => true,
                'message' => 'OTP resend Successfully',
                'validity' => $validity,
                'otp' => $otp
            ];
        } catch (\Exception $e) {
            Log::error('OTP send fail:' . $e->getMessage());
            return [
                'succss' => false,
                'message' => 'Otp resend failed:'  . $e->getMessage(),
            ];
        }
    }


    // ===============End========================

    public function authenticate(array $credentials): array
    {
        // Find user by email
        $user = User::where('email', $credentials['email'])->first();

        // Check if user exists and password matches
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if user has POS role (role_id = 11)
        if ($user->role_id !== 11) {
            throw ValidationException::withMessages([
                'email' => ['You are not authorized to access the POS system.'],
            ]);
        }

        // Check if user is active (optional - add if you have status field)
        if (isset($user->status) && $user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Your account is not active.'],
            ]);
        }

        // Generate token with POS abilities
        $token = $user->createToken('pos_auth_token', ['pos-access'])->plainTextToken;

        return [
            'user' => collect($user->toArray())->only(['first_name', 'email', 'location', 'phone'])->map(function ($value, $key) {
                return $value;
            }),
            'token' => $token,
            'message' => 'POS Login successful',
        ];
    }

    /**
     * Logout POS user
     */
    public function userProfile()
    {
        $user = auth()->user(0);

        return [
            'user' => collect($user->toArray())->only(['first_name', 'last_name', 'email', 'location', 'phone'])->map(function ($value, $key) {
                return $value;
            }),
        ];
    }

    public function emailStatus()
    {
            $userId = Auth::id();
            return User::where('id',$userId)->value('email_verified');
    }

    public function logout(User $user): bool
    {
        // Delete current access token
        $user->currentAccessToken()->delete();

        return true;
    }
}
