<?php
// app/Services/Merchant/TransactionService.php

namespace App\Services\Merchant;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    /**
     * Calculate fees and splits for a transaction
     */
    public function calculateTransactionAmounts(float $totalAmount, string $paymentMethod): array
    {
        // Platform fee percentage (3.5%)
        $platformFeePercentage = 3.5;
        
        // Calculate platform fee
        $platformFee = ($totalAmount * $platformFeePercentage) / 100;
        
        // Amount after platform fee deduction
        $amountAfterPlatformFee = $totalAmount - $platformFee;
        
        // Split the amount after platform fee: Merchant 90%, Admin 10%
        $merchantSharePercentage = 90;
        $adminSharePercentage = 10;
        
        $merchantShare = ($amountAfterPlatformFee * $merchantSharePercentage) / 100;
        $adminShare = ($amountAfterPlatformFee * $adminSharePercentage) / 100;
        
        return [
            'total_amount' => round($totalAmount, 2),
            'platform_fee' => round($platformFee, 2),
            'amount_after_platform_fee' => round($amountAfterPlatformFee, 2),
            'merchant_share' => round($merchantShare, 2),
            'admin_share' => round($adminShare, 2),
            'fee_breakdown' => [
                'platform_fee_percentage' => $platformFeePercentage,
                'platform_fee_amount' => round($platformFee, 2),
                'merchant_share_percentage' => $merchantSharePercentage,
                'admin_share_percentage' => $adminSharePercentage,
                'calculation_details' => [
                    'formula' => "Total - Platform Fee (3.5%) = Amount After Fee, then split Merchant (90%) / Admin (10%)",
                    'total_amount' => $totalAmount,
                    'platform_fee_deduction' => round($platformFee, 2),
                    'amount_for_split' => round($amountAfterPlatformFee, 2),
                    'merchant_share_amount' => round($merchantShare, 2),
                    'admin_share_amount' => round($adminShare, 2)
                ]
            ]
        ];
    }
    
    /**
     * Update merchant's wallet balance
     */
    protected function updateMerchantWallet(int $merchantId, float $amount): void
    {
        try {
            $user = User::find($merchantId);
            
            if (!$user) {
                Log::warning('Merchant not found for wallet update', [
                    'merchant_id' => $merchantId
                ]);
                return;
            }
            
            // Add amount to wallet
            $user->wallet_amount = ($user->wallet_amount ?? 0) + $amount;
            $user->save();
            
            Log::info('Merchant wallet updated', [
                'merchant_id' => $merchantId,
                'amount_added' => $amount,
                'new_wallet_balance' => $user->wallet_amount
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update merchant wallet', [
                'merchant_id' => $merchantId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Create a new transaction record
     */
    public function createTransaction(array $data): Transaction
    {
        try {
            DB::beginTransaction();
            
            // Calculate amounts if not already provided
            if (!isset($data['platform_fee']) && isset($data['total_amount'])) {
                $calculatedAmounts = $this->calculateTransactionAmounts(
                    $data['total_amount'],
                    $data['payment_method'] ?? 'unknown'
                );
                
                $data = array_merge($calculatedAmounts, $data);
            }
            
            // Ensure merchant_cyclos_id is set from authenticated user
            if (empty($data['merchant_cyclos_id']) && auth()->check()) {
                $data['merchant_cyclos_id'] = auth()->user()->cyclos_user_id;
            }
            
            // Set merchant_id from authenticated user
            if (empty($data['merchant_id']) && auth()->check()) {
                $data['merchant_id'] = auth()->id();
            }
            
            // Set paid_at timestamp
            if (empty($data['paid_at'])) {
                $data['paid_at'] = now();
            }
            
            // Create the transaction
            $transaction = Transaction::create($data);
            
            // Update merchant's wallet if transaction is completed
            if ($transaction->payment_status === 'completed' && $transaction->merchant_share > 0) {
                $this->updateMerchantWallet($transaction->merchant_id, $transaction->merchant_share);
            }
            
            DB::commit();
            
            Log::info('Transaction created successfully', [
                'transaction_id' => $transaction->id,
                'total_amount' => $transaction->total_amount,
                'platform_fee' => $transaction->platform_fee,
                'merchant_share' => $transaction->merchant_share,
                'admin_share' => $transaction->admin_share,
                'wallet_updated' => $transaction->payment_status === 'completed'
            ]);
            
            return $transaction;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create transaction', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }
    
    /**
     * Create transaction from card payment
     */
    public function createCardPaymentTransaction(
        array $paymentData,
        float $totalAmount,
        int $customerId,
        string $customerCyclosId,
        array $cartData
    ): Transaction {
        $calculatedAmounts = $this->calculateTransactionAmounts($totalAmount, 'card');
        
        return $this->createTransaction([
            'transaction_id' => $paymentData['transaction_id'] ?? 'CARD_' . time(),
            'customer_id' => $customerId,
            'customer_cyclos_id' => $customerCyclosId,
            'total_amount' => $totalAmount,
            'payment_method' => 'card',
            'payment_status' => 'completed',
            'cart_data' => $cartData,
            'payment_response' => $paymentData,
            'paid_at' => now(),
            'platform_fee' => $calculatedAmounts['platform_fee'],
            'amount_after_platform_fee' => $calculatedAmounts['amount_after_platform_fee'],
            'admin_share' => $calculatedAmounts['admin_share'],
            'merchant_share' => $calculatedAmounts['merchant_share'],
            'fee_breakdown' => $calculatedAmounts['fee_breakdown']
        ]);
    }
    
    /**
     * Create transaction from cash payment
     */
    public function createCashPaymentTransaction(
        float $totalAmount,
        float $receivedAmount,
        int $customerId,
        string $customerCyclosId,
        array $cartData
    ): Transaction {
        $calculatedAmounts = $this->calculateTransactionAmounts($totalAmount, 'cash');
        
        return $this->createTransaction([
            'transaction_id' => 'CASH_' . time() . '_' . uniqid(),
            'customer_id' => $customerId,
            'customer_cyclos_id' => $customerCyclosId,
            'total_amount' => $totalAmount,
            'received_amount' => $receivedAmount,
            'payment_method' => 'cash',
            'payment_status' => 'completed',
            'cart_data' => $cartData,
            'paid_at' => now(),
            'platform_fee' => $calculatedAmounts['platform_fee'],
            'amount_after_platform_fee' => $calculatedAmounts['amount_after_platform_fee'],
            'admin_share' => $calculatedAmounts['admin_share'],
            'merchant_share' => $calculatedAmounts['merchant_share'],
            'fee_breakdown' => $calculatedAmounts['fee_breakdown']
        ]);
    }
    
    /**
     * Create transaction from Unyti payment
     */
    public function createUnytiPaymentTransaction(
        float $totalAmount,
        string $unytiUserId,
        int $customerId,
        string $customerCyclosId,
        array $cartData
    ): Transaction {
        $calculatedAmounts = $this->calculateTransactionAmounts($totalAmount, 'unyti');
        
        return $this->createTransaction([
            'transaction_id' => 'UNYTI_' . time() . '_' . uniqid(),
            'customer_id' => $customerId,
            'customer_cyclos_id' => $customerCyclosId,
            'total_amount' => $totalAmount,
            'payment_method' => 'unyti',
            'payment_status' => 'pending', // Unyti payments are pending until confirmed
            'cart_data' => $cartData,
            'payment_response' => [
                'unyti_user_id' => $unytiUserId,
                'status' => 'initiated'
            ],
            'paid_at' => now(),
            'platform_fee' => $calculatedAmounts['platform_fee'],
            'amount_after_platform_fee' => $calculatedAmounts['amount_after_platform_fee'],
            'admin_share' => $calculatedAmounts['admin_share'],
            'merchant_share' => $calculatedAmounts['merchant_share'],
            'fee_breakdown' => $calculatedAmounts['fee_breakdown']
        ]);
    }
    
    /**
     * Update transaction status and wallet when Unyti payment is confirmed
     */
    public function confirmUnytiPayment(int $transactionId): bool
    {
        try {
            DB::beginTransaction();
            
            $transaction = Transaction::findOrFail($transactionId);
            
            if ($transaction->payment_method !== 'unyti') {
                throw new \Exception('Transaction is not a Unyti payment');
            }
            
            if ($transaction->payment_status === 'completed') {
                Log::info('Unyti payment already confirmed', [
                    'transaction_id' => $transactionId
                ]);
                return true;
            }
            
            // Update transaction status
            $transaction->payment_status = 'completed';
            $transaction->payment_response = array_merge(
                $transaction->payment_response ?? [],
                ['confirmed_at' => now()->toDateTimeString()]
            );
            $transaction->save();
            
            // Update merchant's wallet with merchant share
            if ($transaction->merchant_share > 0) {
                $this->updateMerchantWallet($transaction->merchant_id, $transaction->merchant_share);
            }
            
            DB::commit();
            
            Log::info('Unyti payment confirmed and wallet updated', [
                'transaction_id' => $transactionId,
                'merchant_share' => $transaction->merchant_share
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to confirm Unyti payment', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Reverse transaction and deduct from wallet (for refunds)
     */
    public function reverseTransaction(int $transactionId, string $reason = 'Refund'): bool
    {
        try {
            DB::beginTransaction();
            
            $transaction = Transaction::findOrFail($transactionId);
            
            if ($transaction->payment_status !== 'completed') {
                throw new \Exception('Only completed transactions can be reversed');
            }
            
            // Deduct from merchant's wallet
            $user = User::find($transaction->merchant_id);
            if ($user) {
                $user->wallet_amount = max(0, ($user->wallet_amount ?? 0) - $transaction->merchant_share);
                $user->save();
                
                Log::info('Wallet deducted for refund', [
                    'merchant_id' => $transaction->merchant_id,
                    'amount_deducted' => $transaction->merchant_share,
                    'new_balance' => $user->wallet_amount,
                    'transaction_id' => $transactionId,
                    'reason' => $reason
                ]);
            }
            
            // Update transaction status
            $transaction->payment_status = 'refunded';
            $transaction->payment_response = array_merge(
                $transaction->payment_response ?? [],
                [
                    'refunded_at' => now()->toDateTimeString(),
                    'refund_reason' => $reason
                ]
            );
            $transaction->save();
            
            DB::commit();
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reverse transaction', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get merchant's wallet balance
     */
    public function getMerchantWalletBalance(int $merchantId): float
    {
        $user = User::find($merchantId);
        return $user ? ($user->wallet_amount ?? 0) : 0;
    }
    
    /**
     * Get transaction summary for merchant
     */
    public function getTransactionSummary(int $merchantId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = Transaction::where('merchant_id', $merchantId)
                          ->where('payment_status', 'completed');
        
        if ($startDate && $endDate) {
            $query->whereBetween('paid_at', [$startDate, $endDate]);
        }
        
        return [
            'total_transactions' => $query->count(),
            'total_amount' => $query->sum('total_amount'),
            'total_platform_fee' => $query->sum('platform_fee'),
            'total_merchant_share' => $query->sum('merchant_share'),
            'total_admin_share' => $query->sum('admin_share'),
            'current_wallet_balance' => $this->getMerchantWalletBalance($merchantId),
            'by_payment_method' => $query->selectRaw('payment_method, COUNT(*) as count, SUM(merchant_share) as total')
                ->groupBy('payment_method')
                ->get()
        ];
    }
}