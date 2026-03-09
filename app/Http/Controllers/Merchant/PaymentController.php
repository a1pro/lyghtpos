<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\Merchant\PaymentService;
use App\Http\Requests\Merchant\ProcessPaymentRequest;
use App\Models\Representation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * Show payment form
     */
    public function index()
    {
        return view('merchant.pages.payments.index');
    }

    /**
     * Show payment form for processing
     */
    public function create(Request $request)
    {
        // Store amount in session if coming from dashboard
        if ($request->has('amount')) {
            session(['payment_amount' => $request->amount]);
        }
        
        return view('merchant.pages.payments.create');
    }

    /**
     * Process payment
     */
    public function process(ProcessPaymentRequest $request)
    {
        // Start database transaction
        DB::beginTransaction();
        
        try {
            // Process the card payment using Authorize.net
            $result = $this->paymentService->processCardPayment(
                $request->validated(),
                $request->amount
            );

            if ($result['success']) {
                // Get the current user's representation
                $representation = Representation::where('user_id', auth()->id())
                    ->orderBy('created_at', 'asc')
                    ->first();
                
                if ($representation) {
                    // First update payment_status to complete
                    $representation->payment_status = 'complete';
                    $representation->save();
                    
                    // Log the payment_status update
                    Log::info('Representation payment_status updated to complete', [
                        'user_id' => auth()->id(),
                        'representation_id' => $representation->id
                    ]);
                    
                    // Then update status to approved
                    $representation->status = 'approved';
                    $representation->save();
                    
                    // Log the status update
                    Log::info('Representation status updated to approved', [
                        'user_id' => auth()->id(),
                        'representation_id' => $representation->id
                    ]);
                    
                    // Alternative: Update both in one query if preferred
                    // $representation->update([
                    //     'payment_status' => 'complete',
                    //     'status' => 'approved'
                    // ]);
                }
                
                // Commit the transaction
                DB::commit();
                
                // Clear the payment amount from session
                session()->forget('payment_amount');
                
                // Redirect to payment success page with payment ID
                return redirect()->route('merchant.payments.show', $result['payment']->id)
                    ->with('success', 'Payment processed successfully. Transaction ID: ' . $result['transaction_id'])
                    ->with('payment_completed', true);
                    
            } else {
                // Rollback transaction on payment failure
                DB::rollBack();
                
                return back()
                    ->withInput()
                    ->with('error', 'Payment failed: ' . ($result['message'] ?? 'Unknown error occurred'));
            }
            
        } catch (\Exception $e) {
            // Rollback transaction on exception
            DB::rollBack();
            
            Log::error('Payment processing error in controller', [
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'An error occurred while processing your payment. Please try again.');
        }
    }

    /**
     * Show payment details
     */
    public function show($id)
    {
        $payment = $this->paymentService->getPayment($id);
        
        if (!$payment) {
            return redirect()->route('merchant.payments.index')
                ->with('error', 'Payment not found');
        }
        
        return view('merchant.pages.payments.show', compact('payment'));
    }

    /**
     * List all payments
     */
    public function history()
    {
        $payments = $this->paymentService->getAllPayments();
        return view('merchant.pages.payments.history', compact('payments'));
    }
}