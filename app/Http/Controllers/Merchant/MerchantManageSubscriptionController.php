<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Services\Paypal\ModernPayPalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MerchantManageSubscriptionController extends Controller
{
    protected $paypalService;

    public function __construct(ModernPayPalService $paypalService)
    {
        $this->paypalService = $paypalService;
    }

    public function index()
    {
        try {
            $user = Auth::user();
            $currentSubscription = UserSubscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->where('end_date', '>', now())
                ->latest()
                ->first();
            
            $plans = SubscriptionPlan::where('is_active', true)
                ->orderBy('sort_order')
                ->get();
            
            return view('merchant.pages.manage-subscriptions.index', compact('plans', 'currentSubscription'));
        } catch (\Exception $e) {
            Log::error('Error loading plans: ' . $e->getMessage());
            return back()->with('error', 'Failed to load plans. Please try again.');
        }
    }

    public function getPlanDetails($planId)
    {
        try {
            $plan = SubscriptionPlan::findOrFail($planId);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'monthly_price' => $plan->monthly_price,
                    'yearly_price' => $plan->yearly_price,
                    'monthly_price_formatted' => '$' . number_format($plan->monthly_price, 2),
                    'yearly_price_formatted' => '$' . number_format($plan->yearly_price, 2),
                    'products_limit' => $plan->products_limit,
                    'customer_limit' => $plan->customer_limit,
                    'yearly_products_limit' => $plan->yearly_products_limit,
                    'yearly_customer_limit' => $plan->yearly_customer_limit,
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

    public function processPlanChange(Request $request)
    {
        try {
            Log::info('ProcessPlanChange started', ['request' => $request->all()]);
            
            $validator = Validator::make($request->all(), [
                'plan_id' => 'required|integer|exists:subscription_plans,id',
                'billing_type' => 'required|in:monthly,yearly'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $plan = SubscriptionPlan::findOrFail($request->plan_id);
            
            $amount = $request->billing_type === 'monthly' 
                ? $plan->monthly_price 
                : $plan->yearly_price;
            
            $description = $plan->name . ' Plan - ' . ucfirst($request->billing_type) . ' Subscription';

            // Store in session with unique key
            $pendingKey = 'pending_subscription_' . $user->id;
            Session::put($pendingKey, [
                'plan_id' => $request->plan_id,
                'billing_type' => $request->billing_type,
                'amount' => $amount,
                'description' => $description,
                'user_id' => $user->id,
                'timestamp' => now()->timestamp
            ]);
            
            Session::save();

            Log::info('Session data saved', ['key' => $pendingKey]);

            // Create PayPal order
            $result = $this->paypalService->createUpdateOrder($amount, 'USD', $description);

            Log::info('PayPal order creation result', ['result' => $result]);

            if ($result['success']) {
                $orderKey = 'paypal_order_id_' . $user->id;
                Session::put($orderKey, $result['order_id']);
                Session::save();
                
                return response()->json([
                    'success' => true,
                    'approval_url' => $result['approval_url'],
                    'order_id' => $result['order_id']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to create PayPal order'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('ProcessPlanChange error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function paypalSuccess(Request $request)
    {
        try {
            Log::info('PayPal success callback', ['request' => $request->all()]);
            
            $orderId = $request->token;
            $payerId = $request->PayerID;
            
            $user = Auth::user();
            if (!$user) {
                Log::error('User not authenticated in success callback');
                return redirect()->route('merchant.manage-subscriptions.index')
                    ->with('error', 'Please login again.');
            }
            
            $orderKey = 'paypal_order_id_' . $user->id;
            $pendingKey = 'pending_subscription_' . $user->id;
            
            $storedOrderId = Session::get($orderKey);
            $pendingSubscription = Session::get($pendingKey);
            
            Log::info('Session data retrieved', [
                'stored_order_id' => $storedOrderId,
                'pending_subscription' => $pendingSubscription
            ]);

            if (!$storedOrderId || !$pendingSubscription) {
                Log::error('Missing session data');
                return redirect()->route('merchant.manage-subscriptions.index')
                    ->with('error', 'Invalid payment session. Please try again.');
            }

            if ($storedOrderId !== $orderId) {
                Log::error('Order ID mismatch', ['stored' => $storedOrderId, 'received' => $orderId]);
                return redirect()->route('merchant.manage-subscriptions.index')
                    ->with('error', 'Payment verification failed.');
            }

            // Capture payment
            $result = $this->paypalService->captureOrder($orderId);
            
            Log::info('PayPal capture result', ['result' => $result]);

            if ($result['success']) {
                DB::beginTransaction();
                
                try {
                    $plan = SubscriptionPlan::findOrFail($pendingSubscription['plan_id']);
                    
                    $days = $pendingSubscription['billing_type'] === 'monthly' ? 30 : 365;
                    $startDate = now();
                    $endDate = now()->addDays($days);
                    $transactionId = 'PAYPAL-' . ($result['capture_id'] ?? uniqid());

                    // Check for existing active subscription
                    $existingSubscription = UserSubscription::where('user_id', $user->id)
                        ->where('status', 'active')
                        ->first();

                    if ($existingSubscription) {
                        // Update existing subscription instead of creating new one
                        $existingSubscription->update([
                            'plan_id' => $plan->id,
                            'billing_type' => $pendingSubscription['billing_type'],
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'transaction_id' => $transactionId,
                            'amount_paid' => $pendingSubscription['amount'],
                            'payment_method' => 'paypal',
                            'payment_status' => 'completed',
                            'paypal_order_id' => $orderId,
                            'paypal_capture_id' => $result['capture_id'] ?? null,
                            'status' => 'active',
                            'updated_at' => now()
                        ]);
                        
                        $subscription = $existingSubscription;
                        Log::info('Updated existing subscription', ['subscription_id' => $subscription->id]);
                    } else {
                        // Create new subscription if none exists
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
                            'paypal_capture_id' => $result['capture_id'] ?? null,
                            'status' => 'active'
                        ]);
                        Log::info('Created new subscription', ['subscription_id' => $subscription->id]);
                    }

                    // Update user status
                    $user->update(['subscribed' => 'yes']);

                    // Clear session
                    Session::forget([$orderKey, $pendingKey]);
                    
                    DB::commit();

                    return redirect()->route('merchant.dashboard')
                        ->with('success', 'Subscription activated successfully!');

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Subscription creation/update error: ' . $e->getMessage());
                    return redirect()->route('merchant.manage-subscriptions.index')
                        ->with('error', 'Payment successful but subscription activation failed. Contact support.');
                }
            } else {
                return redirect()->route('merchant.manage-subscriptions.index')
                    ->with('error', 'Payment capture failed: ' . ($result['message'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            Log::error('PayPal success callback error: ' . $e->getMessage());
            return redirect()->route('merchant.manage-subscriptions.index')
                ->with('error', 'An error occurred while processing your payment.');
        }
    }

    public function paypalCancel(Request $request)
    {
        Log::info('PayPal payment cancelled');
        
        $user = Auth::user();
        if ($user) {
            $orderKey = 'paypal_order_id_' . $user->id;
            $pendingKey = 'pending_subscription_' . $user->id;
            Session::forget([$orderKey, $pendingKey]);
        }

        return redirect()->route('merchant.manage-subscriptions.index')
            ->with('info', 'Payment was cancelled. You can try again anytime.');
    }
}