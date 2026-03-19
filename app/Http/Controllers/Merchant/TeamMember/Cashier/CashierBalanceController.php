<?php
// app/Http/Controllers/Merchant/TeamMember/Cashier/CashierBalanceController.php

namespace App\Http\Controllers\Merchant\TeamMember\Cashier;

use App\Http\Controllers\Controller;
use App\Services\Merchant\TeamMember\Cashier\CashierBalanceService;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Auth;
class CashierBalanceController extends Controller
{
    protected CashierBalanceService $balanceService;

    public function __construct(CashierBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Display the merchant balance dashboard
     */
    public function index(Request $request): Factory|View|JsonResponse
    {
        // Check if user is cashier (role 10)
        if (Auth::user()->role_id !== 10) {
            abort(403, 'Unauthorized access. Cashier access only.');
        }

        $effectiveMerchant = $this->balanceService->getEffectiveMerchant();
        
        if (!$effectiveMerchant) {
            return $request->wantsJson()
                ? response()->json(['error' => 'Unable to determine merchant credentials'], 400)
                : view('merchant.team-members.cashier.pages.balance.index', [
                    'error' => 'Unable to load merchant balance. Please contact support.'
                ]);
        }

        try {
            // Get balance and transactions
            $balance = $this->balanceService->getMerchantBalance();
            $transactions = $this->balanceService->getMerchantTransactions(10, 0);
            $accountDetails = $this->balanceService->getMerchantAccountDetails();
            $cashierInfo = $this->balanceService->getCashierInfo();
            
            // Format balance
            $formattedBalance = $this->balanceService->formatBalance($balance);

            // Get recent transactions (first 5 for dashboard)
            $recentTransactions = array_slice($transactions, 0, 5);

            return $request->wantsJson()
                ? response()->json([
                    'balance' => $balance,
                    'formatted_balance' => $formattedBalance,
                    'transactions' => $transactions,
                    'account_details' => $accountDetails
                ])
                : view('merchant.team-members.cashier.pages.balance.index', [
                    'balance' => $balance,
                    'formattedBalance' => $formattedBalance,
                    'transactions' => $transactions,
                    'recentTransactions' => $recentTransactions,
                    'accountDetails' => $accountDetails,
                    'cashierInfo' => $cashierInfo,
                    'merchantName' => $effectiveMerchant->first_name . ' ' . $effectiveMerchant->last_name,
                    'currencySymbol' => $accountDetails['currency_symbol'] ?? 'TT'
                ]);

        } catch (\Exception $e) {
            Log::error('Error in cashier balance index', [
                'error' => $e->getMessage(),
                'cashier_id' => Auth::id()
            ]);

            return $request->wantsJson()
                ? response()->json(['error' => 'Failed to load balance data'], 500)
                : view('merchant.team-members.cashier.pages.balance.index', [
                    'error' => 'Failed to load balance data. Please try again.'
                ]);
        }
    }

    /**
     * Get balance via AJAX
     */
    public function getBalance(Request $request): JsonResponse
    {
        try {
            $balance = $this->balanceService->getMerchantBalance();
            $formattedBalance = $this->balanceService->formatBalance($balance);

            return response()->json([
                'success' => true,
                'balance' => $balance,
                'formatted_balance' => $formattedBalance
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching balance via AJAX', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch balance'
            ], 500);
        }
    }

    /**
     * Get transactions via AJAX with pagination
     */
    public function getTransactions(Request $request): JsonResponse
    {
        try {
            $page = $request->input('page', 0);
            $perPage = $request->input('per_page', 20);

            $transactions = $this->balanceService->getMerchantTransactions($perPage, $page);

            return response()->json([
                'success' => true,
                'transactions' => $transactions,
                'page' => $page,
                'per_page' => $perPage
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching transactions via AJAX', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transactions'
            ], 500);
        }
    }

    /**
     * Get account details via AJAX
     */
    public function getAccountDetails(Request $request): JsonResponse
    {
        try {
            $accountDetails = $this->balanceService->getMerchantAccountDetails();

            return response()->json([
                'success' => true,
                'account_details' => $accountDetails
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching account details via AJAX', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch account details'
            ], 500);
        }
    }
}