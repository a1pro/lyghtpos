<?php

namespace App\Services\Api\POS\Auth;

use App\Mail\POS\Customer\ForgotPasswordOtpMail;
use App\Models\Customer;
use App\Models\User;
use App\Models\Merchant;
use App\Models\UserAddress;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Services\Cyclos\CyclosUserService;

class PosAuthService
{
    protected $cyclosUserService;

    public function __construct(CyclosUserService $cyclosUserService)
    {
        $this->cyclosUserService = $cyclosUserService;
    }

    public function register(array $data): array
    {
        // Check for existing email
        if (User::where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages(['email' => 'Email is already taken']);
        }

        // Check for existing phone
        if (User::where('phone', $data['phone'])->exists()) {
            throw ValidationException::withMessages(['phone' => 'Phone number is already taken']);
        }

        $referalCode  = $data['referal_code'] ?? null;
        $merchantCode = null;

        // Validate referral code if provided
        if (!empty($referalCode)) {
            $merchant = Merchant::where('merchant_id', $referalCode)->first();

            if (!$merchant) {
                throw ValidationException::withMessages([
                    'referal_code' => 'Invalid referral code',
                ]);
            }

            $merchantCode = $merchant->user_id;
        }

        // Generate OTP with 15 minutes validity
        $otp            = random_int(100000, 999999);
        $validity       = Carbon::now()->addMinutes(15);
        $validityString = $validity->toDateTimeString();

        DB::beginTransaction();

        try {
            $user = User::create([
                'first_name'    => $data['first_name'],
                'last_name'     => $data['last_name'],
                'email'         => $data['email'],
                'phone'         => $data['phone'],
                'role_id'       => 11,
                'password'      => Hash::make($data['password']),
                'merchant_id'   => $merchantCode,
                'otp'           => $otp,
                'time_validity' => $validity,
            ]);

            $customer = Customer::create([
                'user_id'    => $user->id,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'phone'      => $user->phone,
                'email'      => $user->email,
            ]);

            $address = UserAddress::create([
                'user_id'     => $user->id,
                'address'     => $data['address'],
                'city'        => $data['city'],
                'state'       => $data['state'],
                'postal_code' => $data['postal_code'],
                'country'     => $data['country'],
            ]);

            $cyclosResponse = $this->createOrGetCyclosUser($user);
            $this->updateUserWithCyclosData($user, $cyclosResponse);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('POS registration failed: ' . $e->getMessage(), [
                'email' => $data['email'] ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }

        $token = $user->createToken('pos_auth_token')->plainTextToken;

        return [
            'user'     => $user,
            'token'    => $token,
            'otp'      => $otp,
            'validity' => $validityString,
            'address'  => $address,
            'message'  => 'Registration successful',
        ];
    }

    /**
     * Create or get existing Cyclos user.
     *
     * @throws Exception
     */
    protected function createOrGetCyclosUser(User $user): array
    {
        // If the user already has a Cyclos ID, just return it.
        if ($user->cyclos_user_id) {
            Log::info('User already has Cyclos ID', [
                'user_id'   => $user->id,
                'cyclos_id' => $user->cyclos_user_id,
            ]);

            return [
                'user_id'  => $user->cyclos_user_id,
                'username' => $user->cyclos_username,
                'exists'   => true,
            ];
        }

        Log::info('Creating Cyclos user', [
            'user_id' => $user->id,
            'email'   => $user->email,
        ]);

        $response = $this->cyclosUserService->createUser($user);

        if (!$response || !isset($response['user_id'])) {
            Log::error('Cyclos user creation failed', [
                'user_id'  => $user->id,
                'response' => $response,
            ]);

            throw new Exception('Cyclos user creation failed');
        }

        Log::info('Cyclos user created successfully', [
            'user_id'        => $user->id,
            'cyclos_user_id' => $response['user_id'],
        ]);

        return $response;
    }

    /**
     * Update user with Cyclos data.
     */
    protected function updateUserWithCyclosData(User $user, array $cyclosData): void
    {
        if (isset($cyclosData['user_id'])) {
            $user->cyclos_user_id = $cyclosData['user_id'];
        }
        if (isset($cyclosData['username'])) {
            $user->cyclos_username = $cyclosData['username'];
        }
        if (isset($cyclosData['group'])) {
            $user->cyclos_group = $cyclosData['group'];
        }
        if (isset($cyclosData['password'])) {
            $user->cyclos_user_password = $cyclosData['password'];
        }

        $user->save();

        Log::info('User updated with Cyclos data', [
            'user_id'        => $user->id,
            'cyclos_user_id' => $user->cyclos_user_id,
        ]);
    }

    /**
     * Verify OTP and mark email as verified
     */
    public function otpVerify(array $data)
    {
        try {
            $userId = Auth::id();

            $enteredOtp = $data['otp'];
            $currentTime = Carbon::now();

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

            // Check OTP expiration
            if ($currentTime->gt($timeValidity)) {
                return [
                    'success' => false,
                    'message' => 'OTP has expired'
                ];
            }

            // Validate OTP match
            if ($storedOtp != $enteredOtp) {
                return [
                    'success' => false,
                    'message' => 'Invalid OTP'
                ];
            }

            // Update user verification status
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

    /**
     * Generate and send new OTP to user
     */
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
                'success' => false,
                'message' => 'Otp resend failed:'  . $e->getMessage(),
            ];
        }
    }

    /**
     * Authenticate user credentials and generate token
     */
    public function authenticate(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        // Validate email and password
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check POS role authorization
        // 2 - merchant
        // 11 - customer
        if (!in_array($user->role_id, [2, 11])) {
            throw ValidationException::withMessages([
                'email' => ['You are not authorized to access the LYGHT app.'],
            ]);
        }

        // Verify account status
        if (isset($user->status) && $user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Your account is not active.'],
            ]);
        }

        // Generate POS access token
        $token = $user->createToken('pos_auth_token', ['pos-access'])->plainTextToken;

        return [
            'user' => collect($user->toArray())->only(['first_name', 'email', 'phone'])->map(function ($value, $key) {
                return $value;
            }),
            'role' => $user->role_name,
            'token' => $token,
            'message' => 'POS Login successful',
        ];
    }

    /**
     * Get authenticated user profile data
     */
    public function userProfile()
    {
        $user = User::select('id', 'first_name', 'last_name', 'email', 'phone')->with('address')
            ->find(Auth::id());

        return [
            'user' => $user,
        ];
    }

    /**
     * Get user email verification status
     */
    public function emailStatus()
    {
        $userId = Auth::id();
        return User::where('id', $userId)->value('email_verified');
    }

    /**
     * Logout user by deleting current access token
     */
    public function logout(User $user): bool
    {
        // Delete current access token
        $user->currentAccessToken()->delete();

        return true;
    }

    /**
     * Send forgot password OTP to user's email
     */
    public function sendForgotPasswordOtp(array $data)
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['No account found with this email address.']
            ]);
        }

        if ($user->role_id != 11) {
            throw ValidationException::withMessages([
                'email' => ['You are not authorized to access the POS system.'],
            ]);
        }

        try {
            DB::beginTransaction();

            // Generate otp and validity
            $otp = random_int(100000, 999999);
            $validity = Carbon::now()->addMinutes(15);

            $user->update([
                'forgot_password_otp' => $otp,
                'forgot_password_otp_validity' => $validity
            ]);

            Mail::to($user->email)->send(new ForgotPasswordOtpMail($user, $otp, $validity));

            DB::commit();

            return [
                'success' => true,
                'message' => 'OTP sent successfully',
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('OTP send for forgot password fail: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'OTP send for forgot password failed: ' . $e->getMessage(),
            ];
        }
    }

    public function verifyForgotPasswordOtp(array $data)
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['No account found with this email address.']
            ]);
        }

        if ($user->role_id != 11) {
            throw ValidationException::withMessages([
                'email' => ['You are not authorized to access the POS system.'],
            ]);
        }

        if (!$user->forgot_password_otp || !$user->forgot_password_otp_validity) {
            throw ValidationException::withMessages([
                'otp' => ['No OTP request found. Please request a new OTP.']
            ]);
        }

        // Check if OTP is expired
        if (Carbon::now()->gt($user->forgot_password_otp_validity)) {
            throw ValidationException::withMessages([
                'otp' => ['OTP has expired. Please request a new one.']
            ]);
        }

        if ($user->forgot_password_otp !== $data['otp']) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid OTP. Please try again.']
            ]);
        }

        $user->update([
            'forgot_password_otp' => null,
            'forgot_password_otp_validity' => null,
        ]);

        return [
            'message' => 'OTP verified successfully',
            'email' => $user->email
        ];
    }

    /**
     * Reset password
     */
    public function resetPassword(array $data)
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['No account found with this email address.']
            ]);
        }

        if ($user->role_id != 11) {
            throw ValidationException::withMessages([
                'email' => ['You are not authorized to access the POS system.'],
            ]);
        }

        try {
            DB::beginTransaction();

            $user->password = Hash::make($data['password']);
            $user->save();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Password resets successfully'
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Reset password failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Reset password failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Update profile details for given customer
     */
    public function updateUserProfile(array $data)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();

            if (!$user) {
                throw new Exception("User not found");
            }

            $user->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
            ]);

            $address = UserAddress::updateOrCreate(
                [
                    'user_id' => $user->id,
                ],
                [
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'postal_code' => $data['postal_code'],
                    'country' => $data['country'],
                ]
            );

            DB::commit();

            return [
                'user' => $user,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Update profile failed', [
                'user_id' => Auth::id(),
                'error' => $e->getTraceAsString(),
                'message' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
