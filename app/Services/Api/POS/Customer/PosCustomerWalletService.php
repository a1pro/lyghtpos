<?php

namespace App\Services\Api\POS\Customer;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Services\FavorMember\CyclosPayment\CyclosPaymentService;
class PosCustomerWalletService {

	protected $customerWallet;

	public function __construct(CyclosPaymentService $customerWallet)
	{
		$this->customerWallet = $customerWallet;
	}


	   public function getCustomerWalletBalance(string $cyclosUserId): float
    {
        try {
            // Fetch balance from Cyclos
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
     * Get customer wallet with full details
     * 
     * @param string $cyclosUserId
     * @return array
     */
    public function getCustomerWalletDetails(string $cyclosUserId): array
    {
        try {
            // Get balance
            $balance = $this->customerWallet->getUserBalance($cyclosUserId);
            
            // Get accounts
            $accounts = $this->customerWallet->getUserAccounts($cyclosUserId);
            
            // Get recent transactions (optional)
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
