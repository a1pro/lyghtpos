<?php

namespace App\Http\Controllers\Api\V1\POS\Customer;

use App\Http\Controllers\Controller;
use App\Services\Api\POS\Customer\PosPayPalPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\UserJoiningFees;
use App\Services\Cyclos\CyclosUserService;

class PosPayPalPaymentController extends Controller
{
    protected $paypalService;
    protected $cyclosUserService;

    public function __construct(PosPayPalPaymentService $paypalService, CyclosUserService $cyclosUserService)
    {
        $this->paypalService = $paypalService;
        $this->cyclosUserService = $cyclosUserService;
    }

    /**
     * Step 1: Create PayPal order (API endpoint)
     */
    public function createOrder(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'referral_code' => 'nullable|string'
            ]);

            $userId = Auth::id();
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'status_code' => 401,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $referralCode = $request->input('referral_code');

            // Get absolute URLs for PayPal redirects
            $appUrl = config('app.url');
            $successUrl = $appUrl . '/paypal/success';
            $cancelUrl = $appUrl . '/paypal/cancel';

            Log::info('Creating PayPal order', [
                'user_id' => $userId,
                'amount' => $request->amount,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'referral_code' => $referralCode
            ]);

            $result = $this->paypalService->createOrder(
                $request->amount,
                $userId,
                $successUrl,
                $cancelUrl,
                $referralCode
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'status_code' => 400,
                    'message' => $result['message']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'PayPal order created successfully',
                'data' => [
                    'order_id' => $result['order_id'],
                    'approval_url' => $result['approval_url']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Create order error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PayPal Success Redirect (Web endpoint)
     * This is called by PayPal after successful payment
     */
    public function paypalSuccess(Request $request)
    {
        try {
            $token = $request->query('token');
            $payerId = $request->query('PayerID');

            Log::info('PayPal success callback received', [
                'token' => $token,
                'payer_id' => $payerId
            ]);

            if (!$token) {
                Log::error('No token in PayPal success callback');
                return redirect($this->getFrontendUrl() . '/payment-error?message=No payment token found');
            }

            // Get the payment record
            $userJoiningFee = UserJoiningFees::where('transaction_id', $token)->first();

            if (!$userJoiningFee) {
                Log::error('Payment record not found', ['token' => $token]);
                return redirect($this->getFrontendUrl() . '/payment-error?message=Payment record not found');
            }

            // Get the local user
            $user = User::find($userJoiningFee->user_id);

            if (!$user) {
                Log::error('User not found', ['user_id' => $userJoiningFee->user_id]);
                return redirect($this->getFrontendUrl() . '/payment-error?message=User not found');
            }

            // Check if already completed
            if ($userJoiningFee->status == 'completed') {
                Log::info('Payment already completed', ['token' => $token]);

                // Return local user ID and Cyclos ID
                return redirect($this->getFrontendUrl() . '/payment-success?' . http_build_query([
                    'order_id' => $token,
                    'user_id' => $user->id,  // Send local user ID
                    'cyclos_user_id' => $user->cyclos_user_id,  // Send Cyclos ID
                    'status' => 'success'
                ]));
            }

            // Capture the payment
            Log::info('Capturing payment', ['token' => $token]);
            $captureResult = $this->paypalService->capturePayment($token);

            if (!$captureResult['success']) {
                Log::error('Payment capture failed', [
                    'token' => $token,
                    'error' => $captureResult['message']
                ]);

                return redirect($this->getFrontendUrl() . '/payment-error?message=' . urlencode($captureResult['message']));
            }

            Log::info('Payment captured successfully', ['token' => $token]);

            // Create or get Cyclos user
            $cyclosResponse = $this->createOrGetCyclosUser($user, $userJoiningFee);

            if (!$cyclosResponse || !isset($cyclosResponse['user_id'])) {
                Log::error('Failed to create/get Cyclos user', ['user_id' => $user->id]);
                return redirect($this->getFrontendUrl() . '/payment-error?message=Failed to setup user account');
            }

            // Update user with Cyclos data
            $this->updateUserWithCyclosData($user, $cyclosResponse);

            // Update payment record
            $userJoiningFee->update([
                'status' => 'completed',
                'capture_id' => $captureResult['capture_data']['purchase_units'][0]['payments']['captures'][0]['id'] ?? null
            ]);

            // Redirect to app with local user ID (not Cyclos ID as primary)
            $successUrl = $this->getFrontendUrl() . '/payment-success?' . http_build_query([
                'order_id' => $token,
                'user_id' => $user->id,  // Send local user ID
                'cyclos_user_id' => $cyclosResponse['user_id'],  // Cyclos ID as secondary
                'cyclos_username' => $cyclosResponse['username'] ?? '',
                'status' => 'success'
            ]);

            Log::info('Payment success - Redirecting to app', [
                'user_id' => $user->id,
                'cyclos_user_id' => $cyclosResponse['user_id']
            ]);

            return redirect($successUrl);
        } catch (\Exception $e) {
            Log::error('PayPal success handler error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect($this->getFrontendUrl() . '/payment-error?message=' . urlencode($e->getMessage()));
        }
    }

    /**
     * PayPal Cancel Redirect (Web endpoint)
     */
    public function paypalCancel(Request $request)
    {
        $token = $request->query('token');

        Log::info('PayPal cancel callback', ['token' => $token]);

        if ($token) {
            UserJoiningFees::where('transaction_id', $token)->update(['status' => 'cancelled']);
        }

        return redirect($this->getFrontendUrl() . '/payment-cancelled?message=Payment was cancelled');
    }

    /**
     * Capture payment API endpoint (manual capture from app)
     */
    public function capturePayment(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|string'
            ]);

            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'status_code' => 401,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Check local payment record
            $localPayment = UserJoiningFees::where('transaction_id', $request->order_id)->first();

            if (!$localPayment) {
                return response()->json([
                    'success' => false,
                    'status_code' => 404,
                    'message' => 'Payment record not found'
                ], 404);
            }

            // Check if user is authorized (payment belongs to this user)
            if ($localPayment->user_id != $user->id) {
                return response()->json([
                    'success' => false,
                    'status_code' => 403,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Check if already completed
            if ($localPayment->status == 'completed') {
                return response()->json([
                    'success' => true,
                    'status_code' => 200,
                    'message' => 'Payment already completed',
                    'data' => [
                        'status' => 'COMPLETED',
                        'user_id' => $user->id,
                        'cyclos_user_id' => $user->cyclos_user_id
                    ]
                ]);
            }

            // Check PayPal status
            $statusResult = $this->paypalService->getPaymentStatus($request->order_id);

            if ($statusResult['success'] && $statusResult['status'] == 'COMPLETED') {
                // Update local status
                $localPayment->update(['status' => 'completed']);

                // Create/get Cyclos user if needed
                $cyclosResponse = $this->createOrGetCyclosUser($user, $localPayment);

                if ($cyclosResponse && isset($cyclosResponse['user_id'])) {
                    $this->updateUserWithCyclosData($user, $cyclosResponse);
                }

                return response()->json([
                    'success' => true,
                    'status_code' => 200,
                    'message' => 'Payment already completed',
                    'data' => [
                        'status' => 'COMPLETED',
                        'user_id' => $user->id,
                        'cyclos_user_id' => $user->cyclos_user_id
                    ]
                ]);
            }

            if ($statusResult['status'] != 'APPROVED') {
                return response()->json([
                    'success' => false,
                    'status_code' => 400,
                    'message' => 'Order not approved yet. Current status: ' . $statusResult['status']
                ], 400);
            }

            // Capture the payment
            $result = $this->paypalService->capturePayment($request->order_id);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'status_code' => 400,
                    'message' => $result['message']
                ], 400);
            }

            // Create Cyclos user
            $cyclosResponse = $this->createOrGetCyclosUser($user, $localPayment);

            if (!$cyclosResponse || !isset($cyclosResponse['user_id'])) {
                return response()->json([
                    'success' => false,
                    'status_code' => 500,
                    'message' => 'Payment captured but failed to create user account. Please contact support.',
                    'data' => [
                        'payment_captured' => true,
                        'capture_id' => $result['capture_data']['id'] ?? null
                    ]
                ], 500);
            }

            // Update user with Cyclos data
            $this->updateUserWithCyclosData($user, $cyclosResponse);

            // Update payment record
            $localPayment->update([
                'status' => 'completed',
                'capture_id' => $result['capture_data']['purchase_units'][0]['payments']['captures'][0]['id'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Payment captured successfully',
                'data' => [
                    'capture_id' => $result['capture_data']['id'] ?? null,
                    'status' => 'COMPLETED',
                    'user_id' => $user->id,
                    'cyclos_user_id' => $cyclosResponse['user_id'],
                    'cyclos_username' => $cyclosResponse['username'] ?? null
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Capture payment error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check payment status (API endpoint)
     */
    public function checkPaymentStatus(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|string'
            ]);

            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'status_code' => 401,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $localPayment = UserJoiningFees::where('transaction_id', $request->order_id)->first();

            if (!$localPayment || $localPayment->user_id != $user->id) {
                return response()->json([
                    'success' => false,
                    'status_code' => 404,
                    'message' => 'Payment record not found'
                ], 404);
            }

            $result = $this->paypalService->getPaymentStatus($request->order_id);

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Payment status retrieved',
                'data' => [
                    'order_status' => $result['status'] ?? null,
                    'payment_status' => $localPayment->status,
                    'amount' => $localPayment->amount,
                    'user_id' => $user->id,
                    'cyclos_user_id' => $user->cyclos_user_id,
                    'can_capture' => ($result['status'] ?? '') == 'APPROVED',
                    'is_completed' => $localPayment->status == 'completed'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Check payment status error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment history (API endpoint)
     */
    public function getPaymentHistory(Request $request)
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'status_code' => 401,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $payments = UserJoiningFees::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 15));

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Payment history retrieved',
                'data' => $payments
            ]);
        } catch (\Exception $e) {
            Log::error('Get payment history error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status_code' => 500,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get frontend URL for app deep linking
     */
    protected function getFrontendUrl()
    {
        return config('app.frontend_url', 'yourapp://');
    }

    /**
     * Create or get existing Cyclos user
     */
    protected function createOrGetCyclosUser(User $user, $userJoiningFee = null): ?array
    {
        try {
            // Check if user already has Cyclos ID
            if ($user->cyclos_user_id) {
                Log::info('User already has Cyclos ID', [
                    'user_id' => $user->id,
                    'cyclos_id' => $user->cyclos_user_id
                ]);
                return [
                    'user_id' => $user->cyclos_user_id,
                    'username' => $user->cyclos_username,
                    'exists' => true
                ];
            }

            // Prepare additional data for Cyclos
            $additionalData = [
                'business_name' => $user->business_name ?? ($user->first_name . ' ' . $user->last_name),
                'phone' => $user->phone,
                'location' => $user->location,
                'website' => $user->website ?? '',
                'pos_user_id' => $user->id,
                'role' => 'POS_USER',
                'user_type' => 'POS',
                'referral_code' => $userJoiningFee ? $userJoiningFee->referral_code : null
            ];

            Log::info('Creating Cyclos user', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            $response = $this->cyclosUserService->createUser($user, $additionalData);

            if ($response && isset($response['user_id'])) {
                Log::info('Cyclos user created successfully', [
                    'user_id' => $user->id,
                    'cyclos_user_id' => $response['user_id']
                ]);
                return $response;
            }

            Log::error('Cyclos user creation failed', ['response' => $response]);
            return null;
        } catch (\Exception $e) {
            Log::error('Exception in createOrGetCyclosUser', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Update user with Cyclos data
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
            'user_id' => $user->id,
            'cyclos_user_id' => $user->cyclos_user_id
        ]);
    }
}
