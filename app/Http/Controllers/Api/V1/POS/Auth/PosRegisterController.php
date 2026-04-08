<?php

namespace App\Http\Controllers\Api\V1\POS\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\POS\Auth\PosRegisterRequest;
use App\Mail\POS\Customer\CustomerRegisterMail;
use App\Models\User;
use App\Services\Api\POS\Auth\PosAuthService;
use App\Services\Cyclos\CyclosUserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

            // Create user in Cyclos
         

            DB::commit();

            // ==================================================
            /**
             * Send Mail to POS Customer after successful registration
             * Enter Logs for Mail Sending Success or Failure
             * Try catch for mail is also added to avoid any mail sending failure to affect the registration process
             */ try {
                Mail::to($user->email)->send(new CustomerRegisterMail($user));
                Log::info('Welcome email to POS Customer', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                ]);
            } catch (\Exception $e) {

                Log::warning('Failed to send welcome email to POS customer', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'error' => $e->getMessage(),
                ]);

            }

            // ====================================
            return response()->json([
                'success' => true,
                'status_code' => 201,
                'message' => 'Registration successful',
                'data' => [
                    'user' => $user,
                    'token' => $result['token'],
                   
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
   
}
