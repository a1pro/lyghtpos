<?php

namespace App\Http\Controllers\FavorMember;

use App\Http\Controllers\Controller;
use App\Services\FavorMember\CyclosPayment\CyclosPaymentService;
use App\Services\Merchant\PaymentService;
use App\Http\Requests\FavorMember\AddFundsRequest;
use App\Http\Requests\FavorMember\ProcessPaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WalletController extends Controller
{
    protected $taxRate = 5;
    protected $processingFeeRate = 2.9;
    protected $processingFixedFee = 0.30;

    public function __construct(
        private CyclosPaymentService $cyclosPaymentService,
        private PaymentService $paymentService
    ) {}

    /**
     * Get currency symbol from service
     */
    private function getCurrencySymbol(): string
    {
        return $this->cyclosPaymentService->getCurrencySymbol();
    }

    /**
     * Show dashboard with balance and transactions
     */
    public function dashboard()
    {
        $user = auth()->user();
        
        $balance = 0;
        $recentTransactions = [];
        $error = null;
        $currencySymbol = $this->getCurrencySymbol();

        Log::info('Dashboard accessed', [
            'user_id' => $user->id,
            'currency' => $currencySymbol
        ]);

        if (!$user->cyclos_user_id) {
            $error = 'Cyclos account not linked. Please contact support.';
        } else {
            try {
                $this->cyclosPaymentService->setCurrentUserId($user->cyclos_user_id);
                
                Cache::forget("cyclos_balance_{$user->cyclos_user_id}");
                $balance = $this->cyclosPaymentService->getUserBalance($user->cyclos_user_id);
                
                $rawTransactions = $this->cyclosPaymentService->getUserTransactions($user->cyclos_user_id, 5);
                if (!empty($rawTransactions)) {
                    $recentTransactions = $this->cyclosPaymentService->formatTransactions($rawTransactions);
                }

            } catch (\Exception $e) {
                Log::error('Error in dashboard', ['error' => $e->getMessage()]);
                $error = 'Unable to fetch wallet data.';
            }
        }

        return view('favor-member.pages.dashboard', [
            'balance' => $balance,
            'recentTransactions' => $recentTransactions,
            'error' => $error,
            'currencySymbol' => $currencySymbol
        ]);
    }

    /**
     * Get current balance (AJAX)
     */
    public function getBalance()
    {
        $user = auth()->user();
        
        if (!$user->cyclos_user_id) {
            return response()->json([
                'success' => false,
                'balance' => 0,
                'error' => 'No Cyclos ID found'
            ]);
        }

        try {
            Cache::forget("cyclos_balance_{$user->cyclos_user_id}");
            $balance = $this->cyclosPaymentService->getUserBalance($user->cyclos_user_id);
            $currencySymbol = $this->getCurrencySymbol();
            
            return response()->json([
                'success' => true,
                'balance' => $balance,
                'formatted' => number_format($balance, 2) . ' ' . $currencySymbol,
                'currency' => $currencySymbol
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'balance' => 0,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate all fees and totals
     */
    private function calculateFees(float $amount): array
    {
        $netAmount = $amount;
        $taxAmount = round($netAmount * ($this->taxRate / 100), 2);
        $processingPercentage = round($netAmount * ($this->processingFeeRate / 100), 2);
        $processingTotal = $processingPercentage + $this->processingFixedFee;
        $totalAmount = $netAmount + $taxAmount + $processingTotal;
        
        return [
            'net_amount' => $netAmount,
            'tax_amount' => $taxAmount,
            'processing_percentage' => $processingPercentage,
            'processing_fixed' => $this->processingFixedFee,
            'processing_total' => $processingTotal,
            'total_amount' => $totalAmount,
            'wallet_credit' => $netAmount,
            'tax_rate' => $this->taxRate,
            'processing_rate' => $this->processingFeeRate,
            'currency_symbol' => $this->getCurrencySymbol()
        ];
    }

    /**
     * Process add funds request
     */
    public function addFunds(AddFundsRequest $request)
    {
        $fees = $this->calculateFees($request->amount);
        session(['payment_fees' => $fees]);
        
        return redirect()->route('favor-member.wallet.payment-form');
    }

    /**
     * Show payment form
     */
    public function showPaymentForm()
    {
        $fees = session('payment_fees');
        
        if (!$fees) {
            return redirect()->route('favor-member.dashboard')
                ->with('error', 'Please specify an amount first');
        }

        return view('favor-member.pages.payment-form', $fees);
    }

    /**
     * Process payment
     */
    public function processPayment(ProcessPaymentRequest $request)
    {
        $user = auth()->user();
        $fees = session('payment_fees');
        
        if (!$fees || !$user->cyclos_user_id) {
            return redirect()->route('favor-member.dashboard')
                ->with('error', 'Invalid session or Cyclos account');
        }

        DB::beginTransaction();

        try {
            $paymentResult = $this->paymentService->processCardPayment(
                $request->validated(),
                $fees['total_amount']
            );

            if (!$paymentResult['success']) {
                DB::rollBack();
                return back()->with('error', 'Payment failed: ' . ($paymentResult['message'] ?? ''));
            }

            $description = "Wallet funding: +{$fees['wallet_credit']} {$fees['currency_symbol']} " .
                          "(Tax: {$fees['tax_amount']} {$fees['currency_symbol']}, " .
                          "Processing: {$fees['processing_total']} {$fees['currency_symbol']}) - " .
                          "TXN: {$paymentResult['transaction_id']}";

            $cyclosResult = $this->cyclosPaymentService->addFunds(
                $user->cyclos_user_id,
                $fees['wallet_credit'],
                $description
            );

            if (!$cyclosResult['success']) {
                DB::rollBack();
                return redirect()->route('favor-member.dashboard')
                    ->with('error', 'Payment processed but wallet update failed. TXN: ' . $paymentResult['transaction_id']);
            }

            DB::commit();
            
            session()->forget('payment_fees');
            Cache::forget("cyclos_balance_{$user->cyclos_user_id}");

            return redirect()->route('favor-member.wallet.payment-success', [
                'amount' => $fees['wallet_credit'],
                'tax_amount' => $fees['tax_amount'],
                'processing_fee' => $fees['processing_total'],
                'total_amount' => $fees['total_amount'],
                'transaction_id' => $paymentResult['transaction_id'],
                'currency_symbol' => $fees['currency_symbol']
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment exception', ['error' => $e->getMessage()]);
            return redirect()->route('favor-member.dashboard')
                ->with('error', 'An error occurred');
        }
    }

    /**
     * Payment success page
     */
    public function paymentSuccess(Request $request)
    {
        return view('favor-member.pages.payment-success', [
            'amount' => $request->get('amount'),
            'taxAmount' => $request->get('tax_amount'),
            'processingFee' => $request->get('processing_fee'),
            'totalAmount' => $request->get('total_amount'),
            'transactionId' => $request->get('transaction_id'),
            'currencySymbol' => $request->get('currency_symbol', $this->getCurrencySymbol()),
            'taxRate' => $this->taxRate,
            'processingRate' => $this->processingFeeRate
        ]);
    }

    /**
     * Transactions history page
     */
    public function transactions()
    {
        $user = auth()->user();
        
        $transactions = [];
        $balance = 0;
        $error = null;

        if (!$user->cyclos_user_id) {
            $error = 'Cyclos account not linked.';
        } else {
            try {
                $this->cyclosPaymentService->setCurrentUserId($user->cyclos_user_id);
                
                Cache::forget("cyclos_balance_{$user->cyclos_user_id}");
                $balance = $this->cyclosPaymentService->getUserBalance($user->cyclos_user_id);
                
                $rawTransactions = $this->cyclosPaymentService->getUserTransactions($user->cyclos_user_id, 50);
                $transactions = $this->cyclosPaymentService->formatTransactions($rawTransactions);
                
            } catch (\Exception $e) {
                Log::error('Transactions error', ['error' => $e->getMessage()]);
                $error = 'Unable to load transactions';
            }
        }

        return view('favor-member.pages.transactions', [
            'transactions' => $transactions,
            'error' => $error,
            'balance' => $balance,
            'currencySymbol' => $this->getCurrencySymbol()
        ]);
    }

    /**
     * Get current currency symbol (AJAX)
     */
     
}