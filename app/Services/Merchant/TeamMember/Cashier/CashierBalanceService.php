<?php
// app/Services/Merchant/TeamMember/Cashier/CashierBalanceService.php

namespace App\Services\Merchant\TeamMember\Cashier;

use App\Models\User;
use App\Services\FavorMember\CyclosPayment\CyclosPaymentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CashierBalanceService
{
    protected CyclosPaymentService $cyclosService;

    public function __construct(CyclosPaymentService $cyclosService)
    {
        $this->cyclosService = $cyclosService;
    }

    /**
     * Get the effective merchant (main merchant) for cashier
     */
    public function getEffectiveMerchant(): ?User
    {
        $user = Auth::user();
        
        // Cashier should always have merchant_id (role 10)
        if (!$user->merchant_id) {
            Log::warning('Cashier missing merchant_id', [
                'cashier_id' => $user->id,
                'role_id' => $user->role_id
            ]);
            return null;
        }

        $mainMerchant = User::find($user->merchant_id);
        
        // Check if main merchant exists and has cyclos credentials
        if (!$mainMerchant || !$mainMerchant->cyclos_user_id) {
            Log::warning('Main merchant not found or missing cyclos credentials', [
                'cashier_id' => $user->id,
                'merchant_id' => $user->merchant_id
            ]);
            return null;
        }

        Log::info('Using main merchant credentials for cashier', [
            'cashier_id' => $user->id,
            'main_merchant_id' => $mainMerchant->id,
            'main_merchant_cyclos_id' => $mainMerchant->cyclos_user_id
        ]);

        return $mainMerchant;
    }

    /**
     * Get merchant balance using Cyclos
     */
    public function getMerchantBalance(): float
    {
        $effectiveMerchant = $this->getEffectiveMerchant();
        
        if (!$effectiveMerchant) {
            return 0.00;
        }

        try {
            $balance = $this->cyclosService->getUserBalance($effectiveMerchant->cyclos_user_id);
            
            Log::info('Cashier fetched merchant balance', [
                'cashier_id' => Auth::id(),
                'merchant_id' => $effectiveMerchant->id,
                'balance' => $balance
            ]);

            return $balance;
        } catch (\Exception $e) {
            Log::error('Error fetching merchant balance for cashier', [
                'error' => $e->getMessage(),
                'cashier_id' => Auth::id(),
                'merchant_id' => $effectiveMerchant->id
            ]);
            return 0.00;
        }
    }

    /**
     * Get merchant transactions
     */
    public function getMerchantTransactions(int $pageSize = 20, int $pageNumber = 0): array
    {
        $effectiveMerchant = $this->getEffectiveMerchant();
        
        if (!$effectiveMerchant) {
            return [];
        }

        try {
            // Set the current user for transaction formatting
            $this->cyclosService->setCurrentUserId($effectiveMerchant->cyclos_user_id);
            
            $transactions = $this->cyclosService->getUserTransactions(
                $effectiveMerchant->cyclos_user_id,
                $pageSize,
                $pageNumber
            );

            $formattedTransactions = $this->cyclosService->formatTransactions($transactions);

            Log::info('Cashier fetched merchant transactions', [
                'cashier_id' => Auth::id(),
                'merchant_id' => $effectiveMerchant->id,
                'count' => count($formattedTransactions)
            ]);

            return $formattedTransactions;
        } catch (\Exception $e) {
            Log::error('Error fetching merchant transactions for cashier', [
                'error' => $e->getMessage(),
                'cashier_id' => Auth::id(),
                'merchant_id' => $effectiveMerchant->id
            ]);
            return [];
        }
    }

    /**
     * Get merchant account details
     */
    public function getMerchantAccountDetails(): array
    {
        $effectiveMerchant = $this->getEffectiveMerchant();
        
        if (!$effectiveMerchant) {
            return [];
        }

        try {
            $accounts = $this->cyclosService->getUserAccounts($effectiveMerchant->cyclos_user_id);
            
            return [
                'merchant_name' => $effectiveMerchant->first_name . ' ' . $effectiveMerchant->last_name,
                'merchant_email' => $effectiveMerchant->email,
                'accounts' => $accounts,
                'currency_symbol' => $this->cyclosService->getCurrencySymbol(),
                'currency_code' => $this->cyclosService->getCurrencyCode()
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching merchant account details for cashier', [
                'error' => $e->getMessage(),
                'cashier_id' => Auth::id(),
                'merchant_id' => $effectiveMerchant->id
            ]);
            return [];
        }
    }

    /**
     * Get cashier information
     */
    public function getCashierInfo(): array
    {
        $user = Auth::user();
        
        return [
            'id' => $user->id,
            'name' => $user->first_name . ' ' . $user->last_name,
            'email' => $user->email,
            'role' => 'Cashier'
        ];
    }

    /**
     * Format balance for display
     */
    public function formatBalance(float $balance): string
    {
        $symbol = $this->cyclosService->getCurrencySymbol();
        return $symbol . ' ' . number_format($balance, 2);
    }
}