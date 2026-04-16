<?php

namespace App\Services\Api\POS\Customer;

use App\Services\FavorMember\CyclosPayment\CyclosPaymentService;

class PosCustomerWalletService
{
    protected $customerWallet;

    /**
     * Inject Cyclos payment service dependency
     */
    public function __construct(CyclosPaymentService $customerWallet)
    {
        $this->customerWallet = $customerWallet;
    }

    /**
     * Get customer wallet balance from Cyclos
     */
    public function getCustomerWalletBalance(string $cyclosUserId): float
    {
        try {
            $balance = $this->customerWallet->getUserBalance($cyclosUserId);

            return $balance;
        } catch (\Exception $e) {
            \Log::error('Failed to get customer wallet balance', [
                'cyclos_user_id' => $cyclosUserId,
                'error' => $e->getMessage()
            ]);

            return 0.00;
        }
    }

    /**
     * Get customer wallet with full details including balance, accounts and transactions
     */
    public function getCustomerWalletDetails(string $cyclosUserId): array
    {
        try {
            // Get balance
            $balance = $this->customerWallet->getUserBalance($cyclosUserId);

            // Get accounts
            $accounts = $this->customerWallet->getUserAccounts($cyclosUserId);

            // Get recent transactions
            $transactions = $this->customerWallet->getUserTransactions($cyclosUserId, 10, 0);
            $formattedTransactions = $this->customerWallet->formatTransactions($transactions);

            return [
                'success' => true,
                'data' => [
                    'cyclos_user_id' => $cyclosUserId,
                    'balance' => $balance,
                    'currency_symbol' => $this->customerWallet->getCurrencySymbol(),
                    'currency_code' => $this->customerWallet->getCurrencyCode(),
                    'accounts' => $accounts,
                    'recent_transactions' => $formattedTransactions
                ]
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to get customer wallet details', [
                'cyclos_user_id' => $cyclosUserId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to fetch wallet details: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
