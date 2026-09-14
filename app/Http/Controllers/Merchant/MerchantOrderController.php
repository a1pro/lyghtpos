<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\HandpointTransactionLog;
use App\Models\MerchantOrder;
use App\Services\Cyclos\MerchantCyclosOrderService;
use App\Services\Handpoint\HandpointPaymentService;
use App\Services\Merchant\MerchantOrderService;
use App\Services\Merchant\Order\MerchantOrderReceiptService;
use App\Services\Procharge\ProchargePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

class MerchantOrderController extends Controller
{
    protected MerchantOrderReceiptService $receiptService;
    protected ProchargePaymentService $prochargeService;
    protected MerchantCyclosOrderService $cyclosService;
    protected HandpointPaymentService $handpointService;

    public function __construct(MerchantOrderReceiptService $receiptService, ProchargePaymentService $prochargeService, MerchantCyclosOrderService $cyclosService, HandpointPaymentService $handpointService)
    {
        $this->receiptService = $receiptService;
        $this->prochargeService = $prochargeService;
        $this->cyclosService = $cyclosService;
        $this->handpointService = $handpointService;
    }

    /**
     * Display a listing of orders for the authenticated merchant.
     */
    public function index(Request $request): View|JsonResponse
    {
        $merchantId = Auth::id();

        $orders = MerchantOrder::with('items')
            ->where('merchant_id', $merchantId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $totalAmount = $orders->sum('total_amount');

        if ($request->wantsJson()) {
            return response()->json([
                'orders' => $orders,
                'total' => $totalAmount
            ]);
        }

        return view('merchant.pages.orders.index', compact('orders', 'totalAmount'));
    }

    /**
     * Display the specified order details.
     */
    public function show(MerchantOrder $order): View
    {
        if ((int) $order->merchant_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this order.');
        }

        // Load items relationship
        $order->load(['items']);

        $handpointLogs = HandpointTransactionLog::where('merchant_order_id', $order->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $customer = $order->customer();

        // Check if refund is eligible
        $isRefundEligible = $this->isRefundEligible($order);
        $refundEligibilityMessage = $this->getRefundEligibilityMessage($order);

        return view('merchant.pages.orders.view', compact(
            'order',
            'customer',
            'handpointLogs',
            'isRefundEligible',
            'refundEligibilityMessage'
        ));
    }

    /**
     * Get order receipt
     */
    public function getReceipt(Request $request, $orderId)
    {
        try {
            $order = MerchantOrder::find($orderId);

            if (!$order) {
                return redirect()->route('merchant.orders.index')
                    ->with('error', 'Order not found');
            }

            // Only allow completed orders
            if ($order->status !== 'completed') {
                return redirect()->route('merchant.orders.index')
                    ->with('error', 'Receipt only available for completed orders');
            }

            return $this->receiptService->generateReceiptHtml($order);
        } catch (\Exception $e) {
            return redirect()->route('merchant.orders.index')
                ->with('error', 'Error generating receipt: ' . $e->getMessage());
        }
    }

    /**
     * Process refund for an order
     * eligible for refund methods
     *  - payment_method = cash, pos_mobile, cyclos_tokens
     *  - pos_mobile_payment_method -> procharge, cyclos_tokens (payment_method is pos_mobile then)
     */
    public function processRefund(Request $request, MerchantOrder $order)
    {
        try {
            // Validate request
            $request->validate([
                'refund_amount' => 'required|numeric|min:0.01',
                'refund_reason' => 'nullable|string|max:500',
            ]);

            // Check if refund is eligible
            if (!$this->isRefundEligible($order)) {
                return redirect()->back()->with('error', 'This order is not eligible for refund.');
            }

            $paymentMethod = $order->payment_method;
            $posMobilePaymentMethod = $order->pos_mobile_payment_method ?? null;

            // Update order to pending status first
            $order->update([
                'refund_status' => 'pending',
                'refund_amount' => $request->refund_amount,
                'refund_notes' => $request->refund_reason,
            ]);

            $refundAmount = $request->refund_amount;
            $refundReason = $request->refund_reason;

            // Handle refund based on payment method
            $result = $this->processRefundByPaymentMethod(
                $order,
                $paymentMethod,
                $posMobilePaymentMethod,
                $refundAmount,
                $refundReason
            );

            if ($result['success']) {
                // Mark as refunded
                $updateData = [
                    'refund_status' => 'refunded',
                    'refund_mode' => $result['refund_mode'],
                    'refund_notes' => $refundReason,
                    'refund_amount' => $refundAmount,
                ];

                // Add specific fields based on refund mode
                if ($result['refund_mode'] === 'cyclos_tokens') {
                    $updateData['refund_cyclos_token_txn_id'] = $result['txn_id'] ?? null;
                    $updateData['refund_cyclos_token_response'] = $result['full_response'] ?? null;
                } elseif ($result['refund_mode'] === 'procharge') {
                    $updateData['refund_procharge_txn_id'] = $result['txn_id'] ?? null;
                    $updateData['refund_procharge_response'] = $result['full_response'] ?? null;
                } elseif ($result['refund_mode'] === 'handpoint_card') {
                    $updateData['refund_handpoint_card_txn_id'] = $result['txn_id'] ?? null;
                    $updateData['refund_handpoint_card_response'] = $result['full_response'] ?? null;
                }

                $order->update($updateData);

                Log::info('Refund processed successfully', [
                    'order_id' => $order->id,
                    'refund_mode' => $result['refund_mode'],
                    'refund_amount' => $refundAmount,
                ]);

                return redirect()->route('merchant.orders.show', $order->id)
                    ->with('success', 'Refund processed successfully.');
            } else {
                // Refund failed - mark as failed
                $order->update([
                    'refund_status' => 'failed',
                    'refund_notes' => $refundReason . ' | Error: ' . ($result['message'] ?? 'Unknown error'),
                    'refund_error_response' => $result['full_response'] ?? null,
                ]);

                Log::error('Refund failed', [
                    'order_id' => $order->id,
                    'payment_method' => $paymentMethod,
                    'error' => $result['message'] ?? 'Unknown error',
                ]);

                return redirect()->back()->with('error', 'Refund failed: ' . ($result['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('Refund exception', [
                'order_id' => $order->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update order status to failed
            if (isset($order)) {
                $order->update([
                    'refund_status' => 'failed',
                ]);
            }

            return redirect()->back()->with('error', 'Refund failed: ' . $e->getMessage());
        }
    }

    /**
     * Process refund based on payment method
     */
    private function processRefundByPaymentMethod(
        MerchantOrder $order,
        string $paymentMethod,
        ?string $posMobilePaymentMethod,
        float $refundAmount,
        ?string $refundReason
    ): array {
        // Cash payment
        if ($paymentMethod === 'cash') {
            return [
                'success' => true,
                'refund_mode' => 'cash',
                'txn_id' => null,
                'full_response' => null,
                'message' => 'Cash refund processed manually.',
            ];
        }

        // Handpoint Card payment (MOTO / Remote Refund)
        if ($paymentMethod === 'card') {
            $orderMerchant = Auth::user()->merchant;

            $refundResult = $this->handpointService->refundTransaction($orderMerchant, $order, $refundAmount);

            if ($refundResult['success']) {
                return [
                    'success'       => true,
                    'refund_mode'   => 'handpoint_card',
                    'txn_id'        => $refundResult['txn_id'] ?? null,
                    'full_response' => $refundResult['full_response'] ?? null,
                    'message'       => $refundResult['message'] ?? 'Handpoint card refund processed.',
                ];
            }

            return [
                'success'       => false,
                'refund_mode'   => 'handpoint_card',
                'txn_id'        => null,
                'full_response' => $refundResult['full_response'] ?? null,
                'message'       => $refundResult['message'] ?? 'Handpoint refund failed.',
            ];
        }

        // Cyclos Tokens payment (direct or via POS Mobile)
        if (
            $paymentMethod === 'cyclos_tokens' ||
            ($paymentMethod === 'pos_mobile' && $posMobilePaymentMethod === 'cyclos_tokens')
        ) {
            return $this->processCyclosTokenRefund($order, $refundAmount, $refundReason);
        }

        // Procharge payment (via POS Mobile)
        if ($paymentMethod === 'pos_mobile' && $posMobilePaymentMethod === 'procharge') {
            return $this->processProchargeRefund($order, $refundAmount, $refundReason);
        }

        // Unknown payment method
        return [
            'success' => false,
            'refund_mode' => 'unknown',
            'txn_id' => null,
            'full_response' => null,
            'message' => 'Refund not supported for this payment method.',
        ];
    }

    /**
     * Process Cyclos Token refund
     */
    private function processCyclosTokenRefund(MerchantOrder $order, float $refundAmount, ?string $refundReason): array
    {
        try {
            // Get the Cyclos order ID
            $cyclosOrderId = $order->cyclos_order_id ?? null;

            if (!$cyclosOrderId) {
                return [
                    'success' => false,
                    'refund_mode' => 'cyclos_tokens',
                    'txn_id' => null,
                    'full_response' => null,
                    'message' => 'Cyclos order ID not found.',
                ];
            }
            // Call Cyclos refund service
            $refundResult = $this->cyclosService->refundOrder($order, $cyclosOrderId, $refundAmount, $refundReason);

            if ($refundResult['success'] && !empty($refundResult['txn_id'])) {
                return [
                    'success' => true,
                    'refund_mode' => 'cyclos_tokens',
                    'txn_id' => $refundResult['txn_id'] ?? null,
                    'full_response' => $refundResult['full_response'] ?? $refundResult,
                    'message' => 'Cyclos token refund processed.',
                ];
            }

            return [
                'success' => false,
                'refund_mode' => 'cyclos_tokens',
                'txn_id' => null,
                'full_response' => $refundResult,
                'message' => $refundResult['message'] ?? 'Cyclos refund failed.',
            ];
        } catch (\Exception $e) {
            Log::error('Cyclos refund error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'refund_mode' => 'cyclos_tokens',
                'txn_id' => null,
                'full_response' => null,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process Procharge refund
     */
    private function processProchargeRefund(MerchantOrder $order, float $refundAmount, ?string $refundReason): array
    {
        try {
            $prochargeCardToken = $order->procharge_card_token ?? null;

            if (!$prochargeCardToken) {
                return [
                    'success' => false,
                    'refund_mode' => 'procharge',
                    'txn_id' => null,
                    'full_response' => null,
                    'message' => 'Procharge card token not found.',
                ];
            }

            // Call Procharge refund service
            $refundResult = $this->prochargeService->refundTransaction(
                $prochargeCardToken,
                $refundAmount,
                $order->id,
            );

            if ($refundResult['success'] && !empty($refundResult['txn_id'])) {
                return [
                    'success' => true,
                    'refund_mode' => 'procharge',
                    'txn_id' => $refundResult['txn_id'] ?? null,
                    'full_response' => $refundResult['full_response'] ?? $refundResult,
                    'message' => 'Procharge refund processed.',
                ];
            }

            return [
                'success' => false,
                'refund_mode' => 'procharge',
                'txn_id' => null,
                'full_response' => $refundResult,
                'message' => $refundResult['message'] ?? 'Procharge refund failed.',
            ];
        } catch (\Exception $e) {
            Log::error('Procharge refund error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'refund_mode' => 'procharge',
                'txn_id' => null,
                'full_response' => null,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if order is eligible for refund
     */
    private function isRefundEligible(MerchantOrder $order): bool
    {
        // Only completed orders can be refunded
        if ($order->status !== 'completed') {
            return false;
        }

        // Already refunded
        if ($order->refund_status === 'refunded') {
            return false;
        }

        $paymentMethod = $order->payment_method;
        $posMobilePaymentMethod = $order->pos_mobile_payment_method ?? null;

        // Card (Handpoint) refund eligibility
        if ($paymentMethod === 'card') {
            return $this->isHandpointCardRefundable($order);
        }

        // Eligible payment methods
        $eligibleMethods = ['cash', 'cyclos_tokens'];

        // Check if payment_method is directly eligible
        if (in_array($paymentMethod, $eligibleMethods)) {
            return true;
        }

        // Check if pos_mobile with eligible sub-method
        if ($paymentMethod === 'pos_mobile') {
            $eligiblePosMethods = ['procharge', 'cyclos_tokens'];
            if (in_array($posMobilePaymentMethod, $eligiblePosMethods)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get refund eligibility message
     */
    private function getRefundEligibilityMessage(MerchantOrder $order): string
    {
        if ($order->status !== 'completed') {
            return 'Refund is only available for completed orders.';
        }

        if ($order->refund_status === 'refunded') {
            return 'This order has already been refunded.';
        }

        $paymentMethod = $order->payment_method;
        $posMobilePaymentMethod = $order->pos_mobile_payment_method ?? null;

        // Get payment method label
        $methodLabels = [
            'cash' => 'Cash',
            'cyclos_tokens' => 'Cyclos Tokens',
            'pos_mobile' => 'POS Mobile',
            'card' => 'Card',
            'hybrid' => 'Hybrid',
        ];

        $methodLabel = $methodLabels[$paymentMethod] ?? ucfirst($paymentMethod);

        // Card (Handpoint)
        if ($paymentMethod === 'card') {
            if (!$order->card_transaction_id) {
                return 'Refund is not available: original Handpoint transaction ID is missing.';
            }
            if (strtoupper($order->handpoint_fin_status ?? '') !== 'AUTHORISED') {
                return 'Refund is not available: original Handpoint transaction is not authorised.';
            }
            return 'Refund available for Handpoint Card payments.';
        }

        if ($paymentMethod === 'pos_mobile') {
            $posLabels = [
                'procharge' => 'Procharge',
                'cyclos_tokens' => 'Cyclos Tokens',
                'paypal' => 'PayPal',
            ];
            $subMethodLabel = $posLabels[$posMobilePaymentMethod] ?? ucfirst($posMobilePaymentMethod);
            return "Refund available for POS Mobile - {$subMethodLabel} payments.";
        }

        if (in_array($paymentMethod, ['cash', 'cyclos_tokens'])) {
            return "Refund available for {$methodLabel} payments.";
        }

        return "Refund is not available for payment method used.";
    }

    /**
     * Check if Handpoint card transaction is refundable
     * Requires card_transaction_id not null and handpoint_fin_status = 'AUTHORISED'
     */
    private function isHandpointCardRefundable(MerchantOrder $order): bool
    {
        return !empty($order->card_transaction_id)
            && strtoupper($order->handpoint_fin_status ?? '') === 'AUTHORISED';
    }
}
