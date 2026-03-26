<?php
// app/Http/Controllers/Admin/AdminPayoutController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePayoutStatusRequest;
use App\Services\Admin\AdminPayoutService;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AdminPayoutController extends Controller
{
    protected AdminPayoutService $payoutService;

    public function __construct(AdminPayoutService $payoutService)
    {
        $this->payoutService = $payoutService;
        
        // Add CSRF token to every response
        $this->middleware(function ($request, $next) {
            $response = $next($request);
            
            // Add CSRF token to response headers for AJAX requests
            if ($response instanceof JsonResponse) {
                $response->header('X-CSRF-TOKEN', csrf_token());
            }
            
            return $response;
        });
    }

    /**
     * Display list of all payouts
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'payment_method', 'date_from', 'date_to', 'search', 'per_page']);
        $payouts = $this->payoutService->getAllPayouts($filters);
        $statistics = $this->payoutService->getPayoutStatistics();
        
        return view('admin.pages.payouts.index', compact('payouts', 'statistics', 'filters'));
    }

    /**
     * Show payout details
     */
    public function show(int $payoutId): View
    {
        $payout = $this->payoutService->getPayoutDetails($payoutId);
        
        if (!$payout) {
            abort(404, 'Payout not found');
        }
        
        // Pass CSRF token to view
        return view('admin.pages.payouts.show', compact('payout'))->with('csrf_token', csrf_token());
    }

    /**
     * Update payout status
     */
    public function updateStatus(Request $request, int $payoutId): JsonResponse
    {
        try {
            // Log the request for debugging
            Log::info('Update status request received', [
                'payout_id' => $payoutId,
                'request_method' => $request->method(),
                'request_headers' => $request->headers->all(),
                'request_input' => $request->all(),
                'user_id' => auth()->id(),
                'user_email' => auth()->user()->email
            ]);
            
            // Validate the request manually
            $validator = validator($request->all(), [
                'status' => 'required|in:processing,completed,failed',
                'transaction_id' => 'required_if:status,completed|nullable|string|max:255',
                'failure_reason' => 'required_if:status,failed|nullable|string|max:500',
                'processed_notes' => 'nullable|string|max:500',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $validated = $validator->validated();
            
            $result = $this->payoutService->updatePayoutStatus($payoutId, [
                'status' => $validated['status'],
                'transaction_id' => $validated['transaction_id'] ?? null,
                'failure_reason' => $validated['failure_reason'] ?? null,
                'processed_notes' => $validated['processed_notes'] ?? null,
            ]);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'payout' => $result['payout']
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
            
        } catch (\Exception $e) {
            Log::error('Error updating payout status', [
                'payout_id' => $payoutId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error updating payout status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get wallet balance for payout
     */
    public function getWalletBalance(int $payoutId): JsonResponse
    {
        try {
            $payout = Payout::with('merchant')->find($payoutId);
            
            if (!$payout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payout not found'
                ], 404);
            }
            
            $merchant = User::find($payout->merchant_id);
            
            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found',
                    'balance' => 0
                ]);
            }
            
            $balance = $merchant->wallet_amount ?? 0;
            
            return response()->json([
                'success' => true,
                'balance' => $balance,
                'formatted_balance' => '$' . number_format($balance, 2)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching wallet balance', [
                'payout_id' => $payoutId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'balance' => 0
            ], 500);
        }
    }

    /**
     * Bulk update payouts
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'payout_ids' => 'required|array',
            'payout_ids.*' => 'exists:payouts,id',
            'status' => 'required|in:processing,completed,failed',
            'failure_reason' => 'required_if:status,failed|nullable|string',
            'transaction_reference' => 'required_if:status,completed|nullable|string',
            'processed_notes' => 'nullable|string',
        ]);
        
        $result = $this->payoutService->bulkUpdateStatus(
            $request->payout_ids,
            $request->status,
            $request->only(['failure_reason', 'transaction_reference', 'processed_notes'])
        );
        
        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'success_count' => $result['success_count'],
                'failed_count' => $result['failed_count']
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 400);
    }

    /**
     * Export payouts
     */
    public function export(Request $request)
    {
        $filters = $request->only(['status', 'payment_method', 'date_from', 'date_to', 'search']);
        return $this->payoutService->exportPayouts($filters);
    }

    /**
     * Get payout statistics
     */
    public function getStatistics(): JsonResponse
    {
        $statistics = $this->payoutService->getPayoutStatistics();
        
        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }
}