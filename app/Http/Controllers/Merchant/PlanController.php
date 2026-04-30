<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Models\UserSubscriptionHistory;
use App\Services\Paypal\ModernPayPalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use DB;

class PlanController extends Controller
{
    protected $paypalService;

    public function __construct(ModernPayPalService $paypalService)
    {
        $this->paypalService = $paypalService;
    }

    /**
     * Display the plans page
     */
    public function index()
    {
        try {
            $plans = SubscriptionPlan::where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            Session::forget('subscribed');
            Session::forget('pending_subscription');
            Session::forget('paypal_order_id');

            return view('merchant.pages.subscription.plans', compact('plans'));
            
        } catch (\Exception $e) {
            Log::error('Error loading plans: ' . $e->getMessage());
            return back()->with('error', 'Failed to load plans. Please try again.');
        }
    }

    /**
     * Get plan details for modal
     */
    public function getPlanDetails($planId)
    {
        try {
            Log::info('Fetching plan details for ID: ' . $planId);
            
            $plan = SubscriptionPlan::find($planId);
            
            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'monthly_price' => $plan->monthly_price,
                    'yearly_price' => $plan->yearly_price,
                    'monthly_price_formatted' => '$' . number_format($plan->monthly_price, 2),
                    'yearly_price_formatted' => '$' . number_format($plan->yearly_price, 2),
                    'yearly_total' => '$' . number_format($plan->yearly_total ?? ($plan->yearly_price * 12), 2)
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching plan details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load plan details'
            ], 500);
        }
    }

    /**
     * Process PayPal payment - Create Order
     */
    public function processPayPalPayment(Request $request)
    {
        try {
            Log::info('Processing PayPal payment request:', $request->all());

            $validator = Validator::make($request->all(), [
                'plan_id' => 'required|integer|exists:subscription_plans,id',
                'billing_type' => 'required|in:monthly,yearly'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $plan = SubscriptionPlan::findOrFail($request->plan_id);
            
            $amount = $request->billing_type === 'monthly' 
                ? $plan->monthly_price 
                : $plan->yearly_price;
            
            $description = $plan->name . ' Plan - ' . ucfirst($request->billing_type) . ' Subscription';

            // Store plan details in session for later use
            Session::put('pending_subscription', [
                'plan_id' => $request->plan_id,
                'billing_type' => $request->billing_type,
                'amount' => $amount,
                'description' => $description,
                'user_id' => Auth::id(),
                'timestamp' => now()->timestamp
            ]);

            // Create PayPal order
            $result = $this->paypalService->createOrder($amount, 'USD', $description);

            Log::info('PayPal create order result:', ['success' => $result['success']]);

            if ($result['success']) {
                // Store order ID in session
                Session::put('paypal_order_id', $result['order_id']);
                
                return response()->json([
                    'success' => true,
                    'approval_url' => $result['approval_url'],
                    'order_id' => $result['order_id']
                ]);
            } else {
                Session::forget('pending_subscription');
                
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('PayPal payment creation error: ' . $e->getMessage());
            
            Session::forget('pending_subscription');
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate PayPal payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle PayPal success callback
     */
    public function paypalSuccess(Request $request)
    {
        try {
            $orderId = $request->token; // PayPal returns order ID as token
            $payerId = $request->PayerID;

            Log::info('PayPal success callback', [
                'orderId' => $orderId,
                'payerId' => $payerId
            ]);

            // Verify with session
            $storedOrderId = Session::get('paypal_order_id');
            $pendingSubscription = Session::get('pending_subscription');

            if (!$storedOrderId || !$pendingSubscription) {
                Log::error('Missing session data');
                return redirect()->route('merchant.subscription.plans')
                    ->with('error', 'Invalid payment session. Please try again.');
            }

            if ($storedOrderId !== $orderId) {
                Log::error('Order ID mismatch');
                return redirect()->route('merchant.subscription.plans')
                    ->with('error', 'Payment verification failed.');
            }

            // Verify user
            if (Auth::id() != $pendingSubscription['user_id']) {
                return redirect()->route('merchant.subscription.plans')
                    ->with('error', 'User verification failed.');
            }

            // Capture payment
            $result = $this->paypalService->captureOrder($orderId);

            Log::info('PayPal capture result:', $result);

            if ($result['success']) {
                DB::beginTransaction();

                try {
                    $user = Auth::user();
                    $plan = SubscriptionPlan::findOrFail($pendingSubscription['plan_id']);

                    $days = $pendingSubscription['billing_type'] === 'monthly' ? 30 : 365;
                    $startDate = now();
                    $endDate = now()->addDays($days);

                    $transactionId = 'PAYPAL-' . ($result['capture_id'] ?? Str::random(10));

                    // Check for existing subscription
                    $existingSubscription = UserSubscription::where('user_id', $user->id)
                        ->where('plan_id', $plan->id)
                        ->where('status', 'active')
                        ->first();

                    if ($existingSubscription) {
                        $newEndDate = $existingSubscription->end_date->addDays($days);
                        
                        $existingSubscription->update([
                            'billing_type' => $pendingSubscription['billing_type'],
                            'end_date' => $newEndDate,
                            'transaction_id' => $transactionId,
                            'amount_paid' => $pendingSubscription['amount'],
                            'payment_method' => 'paypal',
                            'payment_status' => 'completed',
                            'paypal_order_id' => $orderId,
                            'paypal_payer_id' => $payerId,
                            'paypal_capture_id' => $result['capture_id'],
                            'status' => 'active'
                        ]);

                        $subscription = $existingSubscription;
                    } else {
                        // Deactivate other active subscriptions
                        UserSubscription::where('user_id', $user->id)
                            ->where('status', 'active')
                            ->update(['status' => 'superseded']);

                        // Create new subscription
                        $subscription = UserSubscription::create([
                            'user_id' => $user->id,
                            'plan_id' => $plan->id,
                            'billing_type' => $pendingSubscription['billing_type'],
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'transaction_id' => $transactionId,
                            'amount_paid' => $pendingSubscription['amount'],
                            'payment_method' => 'paypal',
                            'payment_status' => 'completed',
                            'paypal_order_id' => $orderId,
                            'paypal_payer_id' => $payerId,
                            'paypal_capture_id' => $result['capture_id'],
                            'status' => 'active'
                        ]);

                        UserSubscriptionHistory::create([
                            'user_id' => $user->id,
                            'plan_id' => $plan->id,
                            'user_subscription_id' => $subscription->id,
                            'billing_type' => $pendingSubscription['billing_type'],
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'transaction_id' => $transactionId,
                            'amount_paid' => $pendingSubscription['amount'],
                            'payment_method' => 'paypal',
                            'status' => 'history'
                        ]);
                    }

                    // Update user status
                    $user->update(['subscribed' => 'yes']);

                    // Clear session
                    Session::forget(['paypal_order_id', 'pending_subscription']);
                    Session::put('subscribed', true);

                    DB::commit();

                    return redirect()->route('merchant.dashboard')
                        ->with('success', 'Subscription activated successfully!');

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Subscription creation error: ' . $e->getMessage());
                    
                    return redirect()->route('merchant.subscription.plans')
                        ->with('error', 'Payment successful but subscription activation failed. Contact support.');
                }
            } else {
                return redirect()->route('merchant.subscription.plans')
                    ->with('error', 'Payment capture failed: ' . ($result['message'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            Log::error('PayPal success callback error: ' . $e->getMessage());
            
            return redirect()->route('merchant.subscription.plans')
                ->with('error', 'An error occurred while processing your payment.');
        }
    }

    /**
     * Handle PayPal cancel callback
     */
    public function paypalCancel(Request $request)
    {
        Log::info('PayPal payment cancelled');
        
        Session::forget(['paypal_order_id', 'pending_subscription']);

        return redirect()->route('merchant.subscription.plans')
            ->with('info', 'Payment was cancelled. You can try again anytime.');
    }
}