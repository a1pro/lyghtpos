<?php
// app/Services/Merchant/WalletService.php

namespace App\Services\Merchant;

use App\Models\User;
use App\Models\Payout;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WalletService
{
    /**
     * Get merchant wallet balance
     */
    public function getWalletBalance(int $merchantId): float
    {
        $user = User::find($merchantId);
        return $user ? ($user->wallet_amount ?? 0) : 0;
    }

    /**
     * Get wallet summary with statistics
     */
    public function getWalletSummary(int $merchantId): array
    {
        $totalEarnings = Transaction::where('merchant_id', $merchantId)
            ->where('payment_status', 'completed')
            ->sum('merchant_share');

        $totalPayouts = Payout::where('merchant_id', $merchantId)
            ->whereIn('status', ['completed', 'processing'])
            ->sum('amount');

        $pendingPayouts = Payout::where('merchant_id', $merchantId)
            ->where('status', 'pending')
            ->sum('amount');

        $availableBalance = $this->getWalletBalance($merchantId);
        $pendingBalance = $totalEarnings - ($totalPayouts + $availableBalance);

        return [
            'total_earnings' => $totalEarnings,
            'total_payouts' => $totalPayouts,
            'available_balance' => $availableBalance,
            'pending_balance' => max(0, $pendingBalance),
            'pending_payouts' => $pendingPayouts,
            'currency' => config('app.currency_symbol', '$'),
        ];
    }

    /**
     * Get transaction history
     */
    public function getTransactionHistory(int $merchantId, int $limit = 10)
    {
        return Transaction::where('merchant_id', $merchantId)
            ->where('payment_status', 'completed')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get payout history
     */
    public function getPayoutHistory(int $merchantId, int $limit = 10)
    {
        return Payout::where('merchant_id', $merchantId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Create a payout request - NO FEE DEDUCTION
     */
    public function createPayoutRequest(int $merchantId, array $data): array
    {
        try {
            DB::beginTransaction();

            $amount = $data['amount'];
            $currentBalance = $this->getWalletBalance($merchantId);

            // Validate amount
            if ($amount <= 0) {
                return [
                    'success' => false,
                    'message' => 'Amount must be greater than 0',
                ];
            }

            if ($amount > $currentBalance) {
                return [
                    'success' => false,
                    'message' => 'Insufficient wallet balance. Available: ' . number_format($currentBalance, 2),
                ];
            }

            if ($amount < 10) {
                return [
                    'success' => false,
                    'message' => 'Minimum payout amount is $10.00',
                ];
            }

            // Generate unique payout reference
            $payoutReference = 'PO-' . strtoupper(uniqid()) . '-' . date('YmdHis');

            // Prepare payment details based on method
            $paymentDetails = [];
            if ($data['payment_method'] === 'bank_transfer') {
                $paymentDetails = [
                    'account_name' => $data['payment_details']['account_name'] ?? null,
                    'account_number' => $data['payment_details']['account_number'] ?? null,
                    'bank_name' => $data['payment_details']['bank_name'] ?? null,
                    'routing_number' => $data['payment_details']['routing_number'] ?? null,
                ];
            } elseif ($data['payment_method'] === 'paypal') {
                $paymentDetails = [
                    'email' => $data['payment_details']['email'] ?? null,
                ];
            } elseif ($data['payment_method'] === 'stripe') {
                $paymentDetails = [
                    'stripe_account_id' => $data['payment_details']['stripe_account_id'] ?? null,
                ];
            }

            // Create payout record - NO FEE DEDUCTION
            $payout = Payout::create([
                'merchant_id' => $merchantId,
                'payout_reference' => $payoutReference,
                'amount' => $amount,
                'fee' => 0, // No fee
                'net_amount' => $amount, // Full amount
                'payment_method' => $data['payment_method'],
                'payment_details' => $paymentDetails,
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'requested_at' => now(),
            ]);

            // Deduct amount from wallet
            $user = User::find($merchantId);
            $user->wallet_amount = $currentBalance - $amount;
            $user->save();

            DB::commit();

            Log::info('Payout request created', [
                'merchant_id' => $merchantId,
                'payout_id' => $payout->id,
                'amount' => $amount,
                'reference' => $payoutReference,
            ]);

            return [
                'success' => true,
                'message' => 'Your payout request of $' . number_format($amount, 2) . ' has been submitted successfully.',
                'payout' => $payout,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create payout', [
                'merchant_id' => $merchantId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process payout request: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel pending payout - REFUND FULL AMOUNT
     */
    public function cancelPayout(int $payoutId, int $merchantId): array
    {
        try {
            DB::beginTransaction();

            $payout = Payout::where('id', $payoutId)
                ->where('merchant_id', $merchantId)
                ->where('status', 'pending')
                ->first();

            if (!$payout) {
                return [
                    'success' => false,
                    'message' => 'Payout not found or cannot be cancelled',
                ];
            }

            // Update payout status
            $payout->status = 'cancelled';
            $payout->notes = ($payout->notes ? $payout->notes . "\n" : '') . 'Cancelled by merchant on ' . now();
            $payout->save();

            // Refund full amount to wallet
            $user = User::find($merchantId);
            $user->wallet_amount = ($user->wallet_amount ?? 0) + $payout->amount;
            $user->save();

            DB::commit();

            Log::info('Payout cancelled', [
                'merchant_id' => $merchantId,
                'payout_id' => $payoutId,
                'amount' => $payout->amount,
            ]);

            return [
                'success' => true,
                'message' => 'Payout cancelled successfully',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to cancel payout', [
                'merchant_id' => $merchantId,
                'payout_id' => $payoutId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to cancel payout: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get payout details
     */
    public function getPayoutDetails(int $payoutId, int $merchantId): ?Payout
    {
        return Payout::where('id', $payoutId)
            ->where('merchant_id', $merchantId)
            ->first();
    }

    /**
     * Get transactions by date range
     */
    public function getTransactionsByDateRange(int $merchantId, $startDate, $endDate)
    {
        return Transaction::where('merchant_id', $merchantId)
            ->where('payment_status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}