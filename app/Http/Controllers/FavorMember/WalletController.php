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
    protected $taxRate = 5; // 5% tax
    protected $processingFeeRate = 2.9; // 2.9% processing fee
    protected $processingFixedFee = 0.30; // $0.30 fixed fee

    public function __construct(
        private CyclosPaymentService $cyclosPaymentService,
        private PaymentService $paymentService
    ) {}

    /**
     * Show dashboard with balance and transactions
     */
    /**
 * Show dashboard with balance and transactions
 */
public function dashboard()
{
    
    $user = auth()->user();
    
    Log::info('=== DASHBOARD CONTROLLER START ===');
    Log::info('User ID: ' . $user->id);
    Log::info('Cyclos User ID: ' . ($user->cyclos_user_id ?? 'null'));
    
    // Initialize variables with default values
    $balance = 0;
    $recentTransactions = [];
    $error = null;

    if (!$user->cyclos_user_id) {
        Log::warning('No cyclos_user_id found');
        $error = 'Cyclos account not linked. Please contact support.';
    } else {
        try {
            // Set user ID for transaction detection
            $this->cyclosPaymentService->setCurrentUserId($user->cyclos_user_id);
            
            // Get fresh balance
            Cache::forget("cyclos_balance_{$user->cyclos_user_id}");
            $balance = $this->cyclosPaymentService->getUserBalance($user->cyclos_user_id);
            
            // Ensure balance is a float
            $balance = floatval($balance);
            
            Log::info('Balance fetched successfully', [
                'balance' => $balance,
                'type' => gettype($balance)
            ]);

            // Get recent transactions
            $rawTransactions = $this->cyclosPaymentService->getUserTransactions($user->cyclos_user_id, 5);
            if (!empty($rawTransactions)) {
                $recentTransactions = $this->cyclosPaymentService->formatTransactions($rawTransactions);
            }

        } catch (\Exception $e) {
            Log::error('Error in dashboard', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $error = 'Unable to fetch wallet data. Please try again later.';
        }
    }

    // Create data array
    $data = [
        'balance' => $balance,
        'recentTransactions' => $recentTransactions,
        'error' => $error
    ];
    
    Log::info('Data prepared for view:', $data);
    Log::info('=== DASHBOARD CONTROLLER END ===');
    
    // Dump data to browser (remove after fixing)

    
    return view('favor-member.pages.dashboard', $data);
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
            
            return response()->json([
                'success' => true,
                'balance' => $balance,
                'formatted' => number_format($balance, 2)
            ]);
            
        } catch (\Exception $e) {
            Log::error('AJAX balance error', ['error' => $e->getMessage()]);
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
        
        // Processing fee: percentage + fixed
        $processingPercentage = round($netAmount * ($this->processingFeeRate / 100), 2);
        $processingTotal = $processingPercentage + $this->processingFixedFee;
        
        // Total to be charged (what customer pays)
        $totalAmount = $netAmount + $taxAmount + $processingTotal;
        
        // Amount that actually goes to wallet (net amount only)
        $walletCredit = $netAmount;
        
        return [
            'net_amount' => $netAmount,
            'tax_amount' => $taxAmount,
            'processing_percentage' => $processingPercentage,
            'processing_fixed' => $this->processingFixedFee,
            'processing_total' => $processingTotal,
            'total_amount' => $totalAmount,
            'wallet_credit' => $walletCredit,
            'tax_rate' => $this->taxRate,
            'processing_rate' => $this->processingFeeRate
        ];
    }

    /**
     * Process add funds request - redirect to payment form
     */
    public function addFunds(AddFundsRequest $request)
    {
        $fees = $this->calculateFees($request->amount);
        
        // Store all fee data in session
        session([
            'payment_fees' => $fees
        ]);
        
        return redirect()->route('favor-member.wallet.payment-form');
    }

    /**
     * Show payment form with fee breakdown
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
     * Process payment and add funds to Cyclos
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
            // Process credit card payment via Authorize.net (charge the total amount)
            $paymentResult = $this->paymentService->processCardPayment(
                $request->validated(),
                $fees['total_amount'] // Charge the total amount including all fees
            );

            if (!$paymentResult['success']) {
                DB::rollBack();
                return back()->with('error', 'Payment failed: ' . ($paymentResult['message'] ?? ''));
            }

            // Add funds to Cyclos wallet (only the net amount)
            $cyclosResult = $this->cyclosPaymentService->addFunds(
                $user->cyclos_user_id,
                $fees['wallet_credit'],
                "Wallet funding: +{$fees['wallet_credit']} TT (Tax: {$fees['tax_amount']} TT, Processing: {$fees['processing_total']} TT) - TXN: {$paymentResult['transaction_id']}"
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
                'transaction_id' => $paymentResult['transaction_id']
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
        $error = 'Cyclos account not linked. Please contact support.';
    } else {
        try {
            // Set the current user ID for transaction detection
            $this->cyclosPaymentService->setCurrentUserId($user->cyclos_user_id);
            
            // Get current balance
            Cache::forget("cyclos_balance_{$user->cyclos_user_id}");
            $balance = $this->cyclosPaymentService->getUserBalance($user->cyclos_user_id);
            
            // Get transactions using the working endpoint
            $rawTransactions = $this->cyclosPaymentService->getUserTransactions($user->cyclos_user_id, 50);
            
            Log::info('Raw transactions count', ['count' => count($rawTransactions)]);
            
            if (!empty($rawTransactions)) {
                $transactions = $this->cyclosPaymentService->formatTransactions($rawTransactions);
                Log::info('Formatted transactions count', ['count' => count($transactions)]);
            } else {
                Log::warning('No transactions returned from Cyclos');
            }
            
        } catch (\Exception $e) {
            Log::error('Transactions error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $error = 'Unable to load transactions: ' . $e->getMessage();
        }
    }

    return view('favor-member.pages.transactions', [
        'transactions' => $transactions,
        'error' => $error,
        'balance' => $balance
    ]);
}
}