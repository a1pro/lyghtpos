<?php

namespace App\Http\Controllers\Api\V1\POS\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\POS\Auth\PosRegisterRequest;
use App\Models\User;
use App\Services\Api\POS\Auth\PosAuthService;
use App\Services\Cyclos\CyclosUserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PosRegisterController extends Controller
{
    protected $posAuthService;

    protected $cyclosUserService;

    public function __construct(PosAuthService $posAuthService, CyclosUserService $cyclosUserService)
    {
        $this->posAuthService = $posAuthService;
        $this->cyclosUserService = $cyclosUserService;
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

            // Create user in Cyclos
            $cyclosResponse = $this->createCyclosUser($user, $request);

            if (! $cyclosResponse || ! isset($cyclosResponse['user_id'])) {
                throw new \Exception('Failed to create user in Cyclos system');
            }

            // Update user with Cyclos ID (if you have a column for it)
            $user->cyclos_user_id = $cyclosResponse['user_id'];
            $user->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'status_code' => 201,
                'message' => 'Registration successful',
                'data' => [
                    'user' => $user,
                    'token' => $result['token'],
                    'cyclos_user_id' => $cyclosResponse['user_id'],
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

    /**
     * Create user in Cyclos system
     */
    protected function createCyclosUser(User $user, PosRegisterRequest $request): ?array
    {
        try {
            // Prepare additional data for Cyclos based on POS user
            $additionalData = [
                'business_name' => $request->business_name ?? ($user->first_name.' '.$user->last_name),
                'phone' => $request->phone,
                'location' => $request->location,
                'website' => $request->website ?? '',
                'pos_user_id' => $user->id,
                'role' => 'POS_USER', // Specify POS role in Cyclos
                'user_type' => 'POS', // Mark as POS user
            ];

            // Call Cyclos service to create user
            $response = $this->cyclosUserService->createUser($user, $additionalData);

            if ($response && isset($response['user_id'])) {
                Log::info('Cyclos user created successfully for POS user', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'cyclos_user_id' => $response['user_id'],
                ]);

                return $response;
            } else {
                Log::error('Failed to create Cyclos user for POS user', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'response' => $response,
                ]);

                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception while creating Cyclos user for POS user', [
                'user_id' => $user->id ?? null,
                'email' => $user->email ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }
}
