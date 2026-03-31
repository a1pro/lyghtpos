<?php

namespace App\Http\Controllers\Api\V1\WP\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Cyclos\CyclosUserService;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use App\Models\Merchant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Requests\Api\WP\Auth\WpRegisterRequest;
use App\Models\Role;

class WpRegisterController extends Controller
{
    /**
     * Register a new user via API
     */
    public function register(WpRegisterRequest $request)
    {
        DB::beginTransaction();
        
        try {
            Log::info('API Registration started', [
                'email' => $request->email,
                'account_type' => $request->account_type
            ]);

            // Create the user
            $user = $this->createUser($request);
            
            if (!$user) {
                throw new \Exception('Failed to create user record');
            }

            Log::info('User created successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'account_type' => $user->account_type
            ]);

            // Create Cyclos account for both FAVOR_MEMBER and TRADE_MERCHANT
            if (in_array($request->account_type, ['FAVOR_MEMBER', 'TRADE_MERCHANT'])) {
                $cyclosData = $this->createCyclosUser($user, $request);
                
                // Update user with Cyclos data if creation was successful
                if ($cyclosData && isset($cyclosData['user_id'])) {
                    $user->update([
                        'cyclos_user_id' => $cyclosData['user_id'],
                        'cyclos_username' => $cyclosData['username'] ?? null,
                        'cyclos_user_password' => $cyclosData['password'] ?? null,
                        'cyclos_group' => $cyclosData['group'] ?? null,
                    ]);
                    
                    Log::info('Cyclos account created successfully', [
                        'user_id' => $user->id,
                        'account_type' => $request->account_type,
                        'cyclos_user_id' => $cyclosData['user_id']
                    ]);
                } else {
                    Log::warning('Cyclos user creation failed', [
                        'user_id' => $user->id,
                        'account_type' => $request->account_type,
                        'email' => $user->email
                    ]);
                    
                    // For TRADE_MERCHANT, you might want to rollback if Cyclos creation fails
                    // Uncomment below if Cyclos creation is mandatory
                    // throw new \Exception('Failed to create Cyclos account for trade merchant');
                }
            }

            DB::commit();

            // Return success response
            return response()->json([
                'status' => 'success',
                'status_code' => 201,
                'message' => 'Registration successful! Please login with your credentials.',
                'data' => [
                    'user_id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'account_type' => $user->account_type,
                    'cyclos_user_id' => $user->cyclos_user_id ?? null,
                    'cyclos_username' => $user->cyclos_username ?? null,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('API Registration failed', [
                'email' => $request->email ?? null,
                'account_type' => $request->account_type ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'status_code' => 500,
                'message' => 'An error occurred while processing your request',
                'error_details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Create user record
     */
    protected function createUser(WpRegisterRequest $request): ?User
    {
        try {
            $merchantId = Str::random(10);

            $roleMap = [
                'TRADE_MERCHANT' => 'merchant',
                'TRADE_MEMBER' => 'trade_member',
                'ORGANIZATION' => 'organization',
                'FAVOR_MEMBER' => 'favor_member',
            ];

            $role = Role::where('name', $roleMap[$request->account_type])->first();

            $userData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => $role->id ?? 2,
                'account_type' => $request->account_type,
            ];

            $user = User::create($userData);

            // Create merchant record for TRADE_MERCHANT
            if ($request->account_type === 'TRADE_MERCHANT') {
                Merchant::create([
                    'user_id' => $user->id,
                    'merchant_id' => $merchantId,
                    'business_name' => $request->business_name,
                    'primary_contact_name' => $request->primary_contact_name,
                    'phone' => $request->phone,
                    'location' => $request->location,
                    'website' => $request->website ?? '',
                    'consent_language_authorizing' => $request->consent_language_authorizing ?? '',
                    'communyti_enrollment' => $request->communyti_enrollment ?? false,
                    'advocate_contact' => $request->advocate_contact ?? '',
                ]);
                
                Log::info('Merchant record created', [
                    'user_id' => $user->id,
                    'merchant_id' => $merchantId
                ]);
            }

            return $user;
            
        } catch (\Exception $e) {
            Log::error('User creation failed', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create Cyclos user for members (FAVOR_MEMBER and TRADE_MERCHANT)
     */
    protected function createCyclosUser(User $user, WpRegisterRequest $request): ?array
    {
        try {
            $cyclosService = app(CyclosUserService::class);
            
            // Prepare additional data for Cyclos based on account type
            $additionalData = [];
            
            if ($request->account_type === 'TRADE_MERCHANT') {
                $additionalData = [
                    'business_name' => $request->business_name,
                    'phone' => $request->phone,
                    'location' => $request->location,
                    'website' => $request->website ?? '',
                    'merchant_id' => Str::random(10), // Generate merchant ID for Cyclos
                ];
            }
            
            $response = $cyclosService->createUser($user, $additionalData);

            if ($response && isset($response['user_id'])) {
                Log::info('Cyclos user created successfully', [
                    'user_id' => $user->id,
                    'account_type' => $request->account_type,
                    'cyclos_user_id' => $response['user_id']
                ]);
                return $response;
            } else {
                Log::error('Failed to create Cyclos user', [
                    'user_id' => $user->id,
                    'account_type' => $request->account_type
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception while creating Cyclos user', [
                'user_id' => $user->id,
                'account_type' => $request->account_type ?? null,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if email already exists
     */
    public function checkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'status_code' => 422,
                'message' => 'Invalid email address',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $email = $request->input('email');
            $exists = User::where('email', $email)->exists();
            
            return response()->json([
                'status' => 'success',
                'status_code' => 200,
                'available' => !$exists,
                'message' => $exists ? 'Email already taken' : 'Email is available'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Email check failed', [
                'email' => $request->email ?? null,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'status_code' => 500,
                'message' => 'Failed to check email availability'
            ], 500);
        }
    }
}