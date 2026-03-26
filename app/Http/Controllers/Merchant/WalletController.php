<?php
// app/Http/Controllers/Merchant/WalletController.php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\PayoutRequest;
use App\Services\Merchant\WalletService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class WalletController extends Controller
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Display wallet dashboard
     * Route: GET /merchant/wallet
     */
    public function index(): View
    {
        $merchantId = auth()->id();
        
        $walletSummary = $this->walletService->getWalletSummary($merchantId);
        $recentTransactions = $this->walletService->getTransactionHistory($merchantId, 10);
        $recentPayouts = $this->walletService->getPayoutHistory($merchantId, 10);
        
        return view('merchant.pages.wallet.index', compact(
            'walletSummary',
            'recentTransactions',
            'recentPayouts'
        ));
    }

    /**
     * Wallet dashboard (alias for index)
     * Route: GET /merchant/wallet/dashboard
     */
    public function dashboard(): View
    {
        return $this->index();
    }

    /**
     * Show payout request form
     * Route: GET /merchant/wallet/payout
     */
    public function showPayoutForm(): View
    {
        $merchantId = auth()->id();
        $currentBalance = $this->walletService->getWalletBalance($merchantId);
        
        return view('merchant.pages.wallet.payout', compact('currentBalance'));
    }

    /**
     * Process payout request
     * Route: POST /merchant/wallet/payout
     */
    public function requestPayout(PayoutRequest $request)
    {
        $merchantId = auth()->id();
        
        // Prepare payment details based on method
        $paymentDetails = [];
        
        switch ($request->payment_method) {
            case 'bank_transfer':
                $paymentDetails = [
                    'account_name' => $request->account_name,
                    'account_number' => $request->account_number,
                    'bank_name' => $request->bank_name,
                    'routing_number' => $request->routing_number,
                ];
                break;
            case 'paypal':
                $paymentDetails = [
                    'email' => $request->paypal_email,
                ];
                break;
            case 'stripe':
                $paymentDetails = [
                    'stripe_account_id' => $request->stripe_account_id,
                ];
                break;
        }
        
        $result = $this->walletService->createPayoutRequest($merchantId, [
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_details' => $paymentDetails,
            'notes' => $request->notes,
        ]);
        
        // Check if request expects JSON (AJAX)
        if ($request->expectsJson() || $request->ajax()) {
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'redirect' => route('merchant.wallet.index')
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }
        
        // Regular form submission
        if ($result['success']) {
            return redirect()->route('merchant.wallet.index')
                ->with('success', $result['message']);
        }
        
        return redirect()->back()
            ->with('error', $result['message'])
            ->withInput();
    }

    /**
     * Show payout details
     * Route: GET /merchant/wallet/payout/{payout}
     */
    public function payoutDetails(int $payoutId): View
    {
        $merchantId = auth()->id();
        $payout = $this->walletService->getPayoutDetails($payoutId, $merchantId);
        
        if (!$payout) {
            abort(404, 'Payout not found');
        }
        
        return view('merchant.pages.wallet.details', compact('payout'));
    }

    /**
     * Cancel a pending payout
     * Route: DELETE /merchant/wallet/payout/{payout}
     */
    public function cancelPayout(int $payoutId)
    {
        $merchantId = auth()->id();
        
        $result = $this->walletService->cancelPayout($payoutId, $merchantId);
        
        // Check if request expects JSON (AJAX)
        if (request()->expectsJson() || request()->ajax()) {
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }
        
        // Regular form submission
        if ($result['success']) {
            return redirect()->route('merchant.wallet.index')
                ->with('success', $result['message']);
        }
        
        return redirect()->back()
            ->with('error', $result['message']);
    }

    /**
     * Display payout and transaction history
     * Route: GET /merchant/wallet/history
     */
    public function history(Request $request): View
    {
        $merchantId = auth()->id();
        $type = $request->get('type', 'all'); // all, payouts, transactions
        $limit = $request->get('limit', 20);
        
        $payouts = [];
        $transactions = [];
        
        if ($type === 'all' || $type === 'payouts') {
            $payouts = $this->walletService->getPayoutHistory($merchantId, $limit);
        }
        
        if ($type === 'all' || $type === 'transactions') {
            $transactions = $this->walletService->getTransactionHistory($merchantId, $limit);
        }
        
        return view('merchant.pages.wallet.history', compact('payouts', 'transactions', 'type'));
    }

    /**
     * Display transactions only
     * Route: GET /merchant/wallet/transactions
     */
    public function transactions(Request $request): View
    {
        $merchantId = auth()->id();
        $limit = $request->get('limit', 20);
        $transactions = $this->walletService->getTransactionHistory($merchantId, $limit);
        
        return view('merchant.pages.wallet.transactions', compact('transactions'));
    }

    /**
     * Get wallet balance via AJAX
     * Route: GET /merchant/wallet/balance
     */
    public function getBalance(): JsonResponse
    {
        $merchantId = auth()->id();
        $balance = $this->walletService->getWalletBalance($merchantId);
        
        return response()->json([
            'success' => true,
            'balance' => $balance,
            'formatted_balance' => config('app.currency_symbol', '$') . ' ' . number_format($balance, 2),
        ]);
    }

    /**
     * Get transactions via AJAX (for pagination/infinite scroll)
     * Route: GET /merchant/wallet/transactions/ajax
     */
    public function getTransactions(Request $request): JsonResponse
    {
        $merchantId = auth()->id();
        $limit = $request->get('limit', 20);
        $page = $request->get('page', 1);
        
        $transactions = $this->walletService->getTransactionHistory($merchantId, $limit);
        
        return response()->json([
            'success' => true,
            'data' => $transactions,
            'page' => $page,
            'limit' => $limit,
            'total' => $transactions->count()
        ]);
    }

    /**
     * Get payouts via AJAX
     * Route: GET /merchant/wallet/payouts/ajax
     */
    public function getPayouts(Request $request): JsonResponse
    {
        $merchantId = auth()->id();
        $limit = $request->get('limit', 20);
        
        $payouts = $this->walletService->getPayoutHistory($merchantId, $limit);
        
        return response()->json([
            'success' => true,
            'data' => $payouts
        ]);
    }

    /**
     * Display wallet report
     * Route: GET /merchant/wallet/report
     */
    public function report(Request $request): View
    {
        $merchantId = auth()->id();
        $startDate = $request->get('start_date', now()->subMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        
        $summary = $this->walletService->getWalletSummary($merchantId);
        $transactions = $this->walletService->getTransactionsByDateRange($merchantId, $startDate, $endDate);
        
        return view('merchant.pages.wallet.report', compact(
            'summary', 
            'transactions', 
            'startDate', 
            'endDate'
        ));
    }

    /**
     * Export wallet transactions
     * Route: GET /merchant/wallet/export
     */
    public function export(Request $request)
    {
        $merchantId = auth()->id();
        $format = $request->get('format', 'csv');
        $startDate = $request->get('start_date', now()->subMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        
        $transactions = $this->walletService->getTransactionsByDateRange($merchantId, $startDate, $endDate);
        
        if ($format === 'csv') {
            return $this->exportToCSV($transactions, $startDate, $endDate);
        }
        
        if ($format === 'excel') {
            return $this->exportToExcel($transactions, $startDate, $endDate);
        }
        
        return redirect()->back()->with('error', 'Invalid export format');
    }

    /**
     * Export to CSV
     */
    protected function exportToCSV($transactions, $startDate, $endDate)
    {
        $filename = 'wallet-transactions-' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fputs($file, "\xEF\xBB\xBF");
            
            // Add headers
            fputcsv($file, [
                'Date', 
                'Transaction ID', 
                'Type', 
                'Amount', 
                'Payment Method', 
                'Status', 
                'Reference'
            ]);
            
            // Add rows
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->created_at ? $transaction->created_at->format('Y-m-d H:i:s') : 'N/A',
                    $transaction->transaction_id ?? 'N/A',
                    'Transaction',
                    number_format($transaction->merchant_share, 2),
                    ucfirst($transaction->payment_method),
                    ucfirst($transaction->payment_status),
                    $transaction->transaction_id
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to Excel (using simple HTML table)
     */
    protected function exportToExcel($transactions, $startDate, $endDate)
    {
        $filename = 'wallet-transactions-' . date('Y-m-d') . '.xls';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $html = $this->generateExcelHTML($transactions, $startDate, $endDate);
        
        return response()->make($html, 200, $headers);
    }

    /**
     * Generate Excel HTML
     */
    protected function generateExcelHTML($transactions, $startDate, $endDate): string
    {
        $html = '<html><head><meta charset="utf-8"><title>Wallet Transactions Report</title></head><body>';
        $html .= '<h2>Wallet Transactions Report</h2>';
        $html .= '<p>Period: ' . $startDate . ' to ' . $endDate . '</p>';
        $html .= '<p>Generated: ' . now()->format('Y-m-d H:i:s') . '</p>';
        $html .= '<table border="1" cellpadding="5" cellspacing="0">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Date</th>';
        $html .= '<th>Transaction ID</th>';
        $html .= '<th>Type</th>';
        $html .= '<th>Amount</th>';
        $html .= '<th>Payment Method</th>';
        $html .= '<th>Status</th>';
        $html .= '<th>Reference</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        
        foreach ($transactions as $transaction) {
            $html .= '<tr>';
            $html .= '<td>' . ($transaction->created_at ? $transaction->created_at->format('Y-m-d H:i:s') : 'N/A') . '</td>';
            $html .= '<td>' . ($transaction->transaction_id ?? 'N/A') . '</td>';
            $html .= '<td>Transaction</td>';
            $html .= '<td>' . number_format($transaction->merchant_share, 2) . '</td>';
            $html .= '<td>' . ucfirst($transaction->payment_method) . '</td>';
            $html .= '<td>' . ucfirst($transaction->payment_status) . '</td>';
            $html .= '<td>' . ($transaction->transaction_id ?? 'N/A') . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</body></html>';
        
        return $html;
    }
}