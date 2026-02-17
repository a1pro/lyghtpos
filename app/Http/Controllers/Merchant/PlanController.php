<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use DB;
class PlanController extends Controller
{
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
                Log::error('Plan not found with ID: ' . $planId);
                return response()->json([
                    'success' => false,
                    'message' => 'Plan not found'
                ], 404);
            }
            
            $response = [
                'success' => true,
                'data' => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'monthly_price' => $plan->monthly_price,
                    'yearly_price' => $plan->yearly_price,
                    'monthly_price_formatted' => '$' . number_format($plan->monthly_price, 0),
                    'yearly_price_formatted' => '$' . number_format($plan->yearly_price, 0),
                    'yearly_total' => '$' . number_format($plan->yearly_total ?? ($plan->yearly_price * 12), 0)
                ]
            ];
            
            Log::info('Plan details response:', $response);
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            Log::error('Error fetching plan details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load plan details. Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process subscription
     */
    public function processSubscription(Request $request)
    {
        DB::beginTransaction();
    
        try {
            Log::info('Processing subscription request:', $request->all());
    
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please login to continue.'
                ], 401);
            }
    
            $validator = Validator::make($request->all(), [
                'plan_id' => 'required|integer|exists:subscription_plans,id',
                'billing_type' => 'required|in:monthly,yearly',
                'card_number' => 'required|string',
                'card_holder' => 'required|string',
                'expiry' => 'required|string',
                'cvc' => 'required|string'
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
    
            $user = Auth::user();
            $plan = SubscriptionPlan::findOrFail($request->plan_id);
    
            $days = $request->billing_type === 'monthly' ? 30 : 365;
            $price = $request->billing_type === 'monthly'
                ? $plan->monthly_price
                : $plan->yearly_price;
    
            // Generate unique transaction ID
            do {
                $transactionId = 'TXN-' . strtoupper(Str::random(10)) . '-' . time();
            } while (UserSubscription::where('transaction_id', $transactionId)->exists());
    
            $startDate = now();
            $endDate = now()->addDays($days);
    
            // 🔥 Check if user already has this plan
            $subscription = UserSubscription::where('user_id', $user->id)
                ->where('plan_id', $plan->id)
                ->first();
    
            if ($subscription) {
    
                // ✅ Update existing subscription
                $subscription->update([
                    'billing_type' => $request->billing_type,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'transaction_id' => $transactionId,
                    'amount_paid' => $price,
                    'status' => 'active'
                ]);
    
                Log::info('Subscription updated successfully:', $subscription->toArray());
    
            } else {
    
                // ✅ Create new subscription
                $subscription = UserSubscription::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'billing_type' => $request->billing_type,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'transaction_id' => $transactionId,
                    'amount_paid' => $price,
                    'status' => 'active'
                ]);
    
                Log::info('Subscription created successfully:', $subscription->toArray());
            }
    
            // Update user subscribed status
            $user->update([
                'subscribed' => 'yes'
            ]);
    
            Session::put('subscribed', true);
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Subscription processed successfully!',
                'transaction_id' => $transactionId,
                'redirect_url' => route('merchant.dashboard')
            ]);
    
        } catch (\Exception $e) {
    
            DB::rollBack();
    
            Log::error('Subscription processing error: ' . $e->getMessage());
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to process subscription.'
            ], 500);
        }
    }
}