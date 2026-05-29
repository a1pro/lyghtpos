<?php

namespace App\Services\Merchant\Order;

use App\Models\MerchantOrder;
use Illuminate\Support\Facades\Log;

class MerchantOrderReceiptService
{
    /**
     * Generate HTML receipt for a completed order
     */
    public function generateReceiptHtml(MerchantOrder $order): string
    {
        // Only generate receipt for completed orders
        if ($order->status !== 'completed') {
            throw new \Exception('Receipt can only be generated for completed orders');
        }

        // Load relationships
        $order->load(['items', 'merchant']);

        // Get customer
        $customer = $order->customer();

        // Generate HTML based on payment method
        return $this->getReceiptHtmlByMethod($order, $customer);
    }

    /**
     * Get receipt HTML based on payment method
     */
    private function getReceiptHtmlByMethod(MerchantOrder $order, $customer): string
    {
        $method = $order->payment_method;

        switch ($method) {
            case 'card':
                return $this->getCardReceiptHtml($order, $customer);
            case 'cyclos_tokens':
                return $this->getTokenReceiptHtml($order, $customer);
            case 'hybrid':
                return $this->getHybridReceiptHtml($order, $customer);
            case 'cash':
                return $this->getCashReceiptHtml($order, $customer);
            case 'pos_mobile':
                return $this->getPosMobileReceiptHtml($order, $customer);
            default:
                return $this->getDefaultReceiptHtml($order, $customer);
        }
    }

    /**
     * Get status badge HTML
     */
    private function getStatusBadge(string $status): string
    {
        $statusClass = match ($status) {
            'completed' => 'badge-success',
            'pending' => 'badge-warning',
            'cancelled' => 'badge-danger',
            default => 'badge-secondary'
        };

        return "<span class=\"{$statusClass}\">" . ucfirst($status) . "</span>";
    }

    /**
     * Get payment method label
     */
    private function getPaymentMethodLabel(string $method): string
    {
        $methodLabels = [
            'cash' => 'Cash',
            'unyti' => 'Unyti Credits',
            'pax' => 'PAX',
            'pos_mobile' => 'POS Mobile',
            'cyclos_tokens' => 'Cyclos Tokens',
            'card' => 'Card',
            'hybrid' => 'Hybrid (Card + Tokens)',
        ];

        return $methodLabels[$method] ?? ucfirst($method);
    }

    /**
     * Receipt for card payments
     */
    private function getCardReceiptHtml(MerchantOrder $order, $customer): string
    {
        $merchant = $order->merchant;
        $items = $order->items;
        $subtotal = $order->total_amount;
        $tax = $order->tax_amount;
        $total = $subtotal + $tax;
        $transactionId = $order->card_transaction_id ?? 'N/A';

        $itemsHtml = $this->generateItemsTableHtml($items);
        $statusBadge = $this->getStatusBadge($order->status);
        $paymentMethodLabel = $this->getPaymentMethodLabel($order->payment_method);
        $customerName = $customer ? ($customer->first_name . ' ' . $customer->last_name) : 'N/A';

        return <<<HTML
                <html>
                <head>
                    <style>
                        body { font-family: monospace; font-size: 12px; margin: 0; padding: 10px; }
                        .receipt { max-width: 300px; margin: 0 auto; }
                        .header { text-align: center; margin-bottom: 15px; }
                        .separator { border-top: 1px dashed #000; margin: 10px 0; }
                        .separator-solid { border-top: 1px solid #000; margin: 10px 0; }
                        .bold { font-weight: bold; }
                        .right { text-align: right; }
                        .left { text-align: left; }
                        .center { text-align: center; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { padding: 5px 0; text-align: left; }
                        .total-line { font-weight: bold; border-top: 1px solid #000; margin-top: 5px; padding-top: 5px; }
                        .footer { text-align: center; margin-top: 20px; font-size: 10px; }
                        .badge-success { color: green; }
                        .badge-warning { color: orange; }
                        .badge-danger { color: red; }
                        .badge-secondary { color: gray; }
                    </style>
                </head>
                <body>
                    <div class="receipt">
                        <div class="header">
                            <div class="bold">{$merchant->name}</div>
                            <div class="small">Order Receipt</div>
                            <div class="separator-solid"></div>
                            <div>Date: {$order->created_at->format('m/d/Y')}</div>
                        </div>

                        <div class="customer-info">
                            <div><strong>Customer:</strong> {$customerName}</div>
                            <div><strong>Order #:</strong> {$order->order_number}</div>
                            <div class="separator"></div>
                        </div>

                        {$itemsHtml}

                        <div class="separator"></div>
                        
                        <div>
                            <div><strong>Subtotal:</strong> <span class="right">\${$subtotal}</span></div>
                            <div><strong>Tax:</strong> <span class="right">\${$tax}</span></div>
                            <div class="total-line"><strong>Grand Total:</strong> <span class="right">\${$total}</span></div>
                        </div>

                        <div class="separator"></div>
                        
                        <div>
                            <div><strong>Payment Method:</strong> {$paymentMethodLabel}</div>
                            <div><strong>Transaction ID:</strong> {$transactionId}</div>
                            <div><strong>Status:</strong> {$statusBadge}</div>
                            <div><strong>Payment Status:</strong> Paid</div>
                        </div>

                        <div class="separator"></div>
                        
                        <div class="footer">
                            <div>** CUSTOMER COPY **</div>
                            <div class="separator"></div>
                            <div>Thank you for your business!</div>
                        </div>
                    </div>
                </body>
                </html>
                HTML;
    }

    /**
     * Receipt for token payments
     */
    private function getTokenReceiptHtml(MerchantOrder $order, $customer): string
    {
        $merchant = $order->merchant;
        $items = $order->items;
        $subtotal = $order->total_amount;
        $tax = $order->tax_amount;
        $total = $subtotal + $tax;
        $tokenAmount = $order->token_amount ?? $total;
        $cyclosTxId = $order->cyclos_transaction_id ?? 'N/A';

        $itemsHtml = $this->generateItemsTableHtml($items);
        $statusBadge = $this->getStatusBadge($order->status);
        $paymentMethodLabel = $this->getPaymentMethodLabel($order->payment_method);
        $customerName = $customer ? ($customer->first_name . ' ' . $customer->last_name) : 'N/A';

        return <<<HTML
                <html>
                <head>
                    <style>
                        body { font-family: monospace; font-size: 12px; margin: 0; padding: 10px; }
                        .receipt { max-width: 300px; margin: 0 auto; }
                        .header { text-align: center; margin-bottom: 15px; }
                        .separator { border-top: 1px dashed #000; margin: 10px 0; }
                        .separator-solid { border-top: 1px solid #000; margin: 10px 0; }
                        .bold { font-weight: bold; }
                        .right { text-align: right; }
                        .left { text-align: left; }
                        .center { text-align: center; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { padding: 5px 0; text-align: left; }
                        .total-line { font-weight: bold; border-top: 1px solid #000; margin-top: 5px; padding-top: 5px; }
                        .footer { text-align: center; margin-top: 20px; font-size: 10px; }
                        .badge-success { color: green; }
                    </style>
                </head>
                <body>
                    <div class="receipt">
                        <div class="header">
                            <div class="bold">{$merchant->name}</div>
                            <div class="small">Order Receipt</div>
                            <div class="separator-solid"></div>
                            <div>Date: {$order->created_at->format('m/d/Y')}</div>
                        </div>

                        <div class="customer-info">
                            <div><strong>Customer:</strong> {$customerName}</div>
                            <div><strong>Order #:</strong> {$order->order_number}</div>
                            <div class="separator"></div>
                        </div>

                        {$itemsHtml}

                        <div class="separator"></div>
                        
                        <div>
                            <div><strong>Subtotal:</strong> <span class="right">\${$subtotal}</span></div>
                            <div><strong>Tax:</strong> <span class="right">\${$tax}</span></div>
                            <div class="total-line"><strong>Grand Total:</strong> <span class="right">\${$total}</span></div>
                            <div><strong>Tokens Used:</strong> <span class="right">{$tokenAmount}</span></div>
                        </div>

                        <div class="separator"></div>
                        
                        <div>
                            <div><strong>Payment Method:</strong> {$paymentMethodLabel}</div>
                            <div><strong>Transaction ID:</strong> {$cyclosTxId}</div>
                            <div><strong>Status:</strong> {$statusBadge}</div>
                            <div><strong>Payment Status:</strong> Paid</div>
                        </div>

                        <div class="separator"></div>
                        
                        <div class="footer">
                            <div>Token payment processed from your Cyclos wallet</div>
                            <div class="separator"></div>
                            <div>** CUSTOMER COPY **</div>
                            <div class="separator"></div>
                            <div>Thank you for your business!</div>
                        </div>
                    </div>
                </body>
                </html>
                HTML;
    }

    /**
     * Receipt for hybrid payments (card + tokens)
     */
    private function getHybridReceiptHtml(MerchantOrder $order, $customer): string
    {
        $merchant = $order->merchant;
        $items = $order->items;
        $subtotal = $order->total_amount;
        $tax = $order->tax_amount;
        $total = $subtotal + $tax;
        $cardAmount = $order->card_amount;
        $tokenAmount = $order->token_amount;
        $cardTxId = $order->card_transaction_id ?? 'N/A';
        $cyclosTxId = $order->cyclos_transaction_id ?? 'N/A';
        $cardTotalWithTax = $cardAmount + $tax;

        $itemsHtml = $this->generateItemsTableHtml($items);
        $statusBadge = $this->getStatusBadge($order->status);
        $paymentMethodLabel = $this->getPaymentMethodLabel($order->payment_method);
        $customerName = $customer ? ($customer->first_name . ' ' . $customer->last_name) : 'N/A';

        return <<<HTML
                <html>
                <head>
                    <style>
                        body { font-family: monospace; font-size: 12px; margin: 0; padding: 10px; }
                        .receipt { max-width: 300px; margin: 0 auto; }
                        .header { text-align: center; margin-bottom: 15px; }
                        .separator { border-top: 1px dashed #000; margin: 10px 0; }
                        .separator-solid { border-top: 1px solid #000; margin: 10px 0; }
                        .bold { font-weight: bold; }
                        .right { text-align: right; }
                        .left { text-align: left; }
                        .center { text-align: center; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { padding: 5px 0; text-align: left; }
                        .total-line { font-weight: bold; border-top: 1px solid #000; margin-top: 5px; padding-top: 5px; }
                        .footer { text-align: center; margin-top: 20px; font-size: 10px; }
                        .badge-success { color: green; }
                        .payment-breakdown { background: #f5f5f5; padding: 5px; margin: 5px 0; }
                    </style>
                </head>
                <body>
                    <div class="receipt">
                        <div class="header">
                            <div class="bold">{$merchant->name}</div>
                            <div class="small">Order Receipt</div>
                            <div class="separator-solid"></div>
                            <div>Date: {$order->created_at->format('m/d/Y')}</div>
                        </div>

                        <div class="customer-info">
                            <div><strong>Customer:</strong> {$customerName}</div>
                            <div><strong>Order #:</strong> {$order->order_number}</div>
                            <div class="separator"></div>
                        </div>

                        {$itemsHtml}

                        <div class="separator"></div>
                        
                        <div>
                            <div><strong>Subtotal:</strong> <span class="right">\${$subtotal}</span></div>
                            <div><strong>Tax:</strong> <span class="right">\${$tax}</span></div>
                            <div class="total-line"><strong>Grand Total:</strong> <span class="right">\${$total}</span></div>
                        </div>

                        <div class="separator"></div>
                        
                        <div class="payment-breakdown">
                            <div><strong>Payment Breakdown:</strong></div>
                            <div>Card Payment: <span class="right">\${$cardTotalWithTax} (incl. tax)</span></div>
                            <div>Token Payment: <span class="right">{$tokenAmount} (excl. tax)</span></div>
                        </div>

                        <div class="separator"></div>
                        
                        <div>
                            <div><strong>Payment Method:</strong> {$paymentMethodLabel}</div>
                            <div><strong>Card Transaction ID:</strong> {$cardTxId}</div>
                            <div><strong>Token Transaction ID:</strong> {$cyclosTxId}</div>
                            <div><strong>Status:</strong> {$statusBadge}</div>
                            <div><strong>Payment Status:</strong> Paid</div>
                        </div>

                        <div class="separator"></div>
                        
                        <div class="footer">
                            <div>** CUSTOMER COPY **</div>
                            <div class="separator"></div>
                            <div>Thank you for your business!</div>
                        </div>
                    </div>
                </body>
                </html>
                HTML;
    }

    /**
     * Receipt for cash payments
     */
    private function getCashReceiptHtml(MerchantOrder $order, $customer): string
    {
        $merchant = $order->merchant;
        $items = $order->items;
        $subtotal = $order->total_amount;
        $tax = $order->tax_amount;
        $total = $subtotal + $tax;

        $itemsHtml = $this->generateItemsTableHtml($items);
        $statusBadge = $this->getStatusBadge($order->status);
        $paymentMethodLabel = $this->getPaymentMethodLabel($order->payment_method);
        $customerName = $customer ? ($customer->first_name . ' ' . $customer->last_name) : 'N/A';

        return <<<HTML
                <html>
                <head>
                    <style>
                        body { font-family: monospace; font-size: 12px; margin: 0; padding: 10px; }
                        .receipt { max-width: 300px; margin: 0 auto; }
                        .header { text-align: center; margin-bottom: 15px; }
                        .separator { border-top: 1px dashed #000; margin: 10px 0; }
                        .separator-solid { border-top: 1px solid #000; margin: 10px 0; }
                        .bold { font-weight: bold; }
                        .right { text-align: right; }
                        .left { text-align: left; }
                        .center { text-align: center; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { padding: 5px 0; text-align: left; }
                        .total-line { font-weight: bold; border-top: 1px solid #000; margin-top: 5px; padding-top: 5px; }
                        .footer { text-align: center; margin-top: 20px; font-size: 10px; }
                        .badge-success { color: green; }
                    </style>
                </head>
                <body>
                    <div class="receipt">
                        <div class="header">
                            <div class="bold">{$merchant->name}</div>
                            <div class="small">Order Receipt</div>
                            <div class="separator-solid"></div>
                            <div>Date: {$order->created_at->format('m/d/Y')}</div>
                        </div>

                        <div class="customer-info">
                            <div><strong>Customer:</strong> {$customerName}</div>
                            <div><strong>Order #:</strong> {$order->order_number}</div>
                            <div class="separator"></div>
                        </div>

                        {$itemsHtml}

                        <div class="separator"></div>
                        
                        <div>
                            <div><strong>Subtotal:</strong> <span class="right">\${$subtotal}</span></div>
                            <div><strong>Tax:</strong> <span class="right">\${$tax}</span></div>
                            <div class="total-line"><strong>Grand Total:</strong> <span class="right">\${$total}</span></div>
                        </div>

                        <div class="separator"></div>
                        
                        <div>
                            <div><strong>Payment Method:</strong> {$paymentMethodLabel}</div>
                            <div><strong>Amount Paid:</strong> <span class="right">\${$total}</span></div>
                            <div><strong>Status:</strong> {$statusBadge}</div>
                            <div><strong>Payment Status:</strong> Paid</div>
                        </div>

                        <div class="separator"></div>
                        
                        <div class="footer">
                            <div>** CUSTOMER COPY **</div>
                            <div class="separator"></div>
                            <div>Thank you for your business!</div>
                        </div>
                    </div>
                </body>
                </html>
                HTML;
    }

    /**
     * Receipt for POS mobile payments
     */
    private function getPosMobileReceiptHtml(MerchantOrder $order, $customer): string
    {
        $merchant = $order->merchant;
        $items = $order->items;
        $subtotal = $order->total_amount;
        $tax = $order->tax_amount;
        $total = $subtotal + $tax;

        $itemsHtml = $this->generateItemsTableHtml($items);
        $statusBadge = $this->getStatusBadge($order->status);
        $paymentMethodLabel = $this->getPaymentMethodLabel($order->payment_method);
        $customerName = $customer ? ($customer->first_name . ' ' . $customer->last_name) : 'N/A';

        return <<<HTML
                <html>
                <head>
                    <style>
                        body { font-family: monospace; font-size: 12px; margin: 0; padding: 10px; }
                        .receipt { max-width: 300px; margin: 0 auto; }
                        .header { text-align: center; margin-bottom: 15px; }
                        .separator { border-top: 1px dashed #000; margin: 10px 0; }
                        .separator-solid { border-top: 1px solid #000; margin: 10px 0; }
                        .bold { font-weight: bold; }
                        .right { text-align: right; }
                        .left { text-align: left; }
                        .center { text-align: center; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { padding: 5px 0; text-align: left; }
                        .total-line { font-weight: bold; border-top: 1px solid #000; margin-top: 5px; padding-top: 5px; }
                        .footer { text-align: center; margin-top: 20px; font-size: 10px; }
                        .badge-success { color: green; }
                    </style>
                </head>
                <body>
                    <div class="receipt">
                        <div class="header">
                            <div class="bold">{$merchant->name}</div>
                            <div class="small">Order Receipt</div>
                            <div class="separator-solid"></div>
                            <div>Date: {$order->created_at->format('m/d/Y')}</div>
                        </div>

                        <div class="customer-info">
                            <div><strong>Customer:</strong> {$customerName}</div>
                            <div><strong>Order #:</strong> {$order->order_number}</div>
                            <div class="separator"></div>
                        </div>

                        {$itemsHtml}

                        <div class="separator"></div>
                        
                        <div>
                            <div><strong>Subtotal:</strong> <span class="right">\${$subtotal}</span></div>
                            <div><strong>Tax:</strong> <span class="right">\${$tax}</span></div>
                            <div class="total-line"><strong>Grand Total:</strong> <span class="right">\${$total}</span></div>
                        </div>

                        <div class="separator"></div>
                        
                        <div>
                            <div><strong>Payment Method:</strong> {$paymentMethodLabel}</div>
                            <div><strong>Status:</strong> {$statusBadge}</div>
                            <div><strong>Payment Status:</strong> Paid</div>
                            <div><em>Order processed via POS Lyght Mobile App</em></div>
                        </div>

                        <div class="separator"></div>
                        
                        <div class="footer">
                            <div>** CUSTOMER COPY **</div>
                            <div class="separator"></div>
                            <div>Thank you for your business!</div>
                        </div>
                    </div>
                </body>
                </html>
                HTML;
    }

    /**
     * Generate items table HTML
     */
    private function generateItemsTableHtml($items): string
    {
        if ($items->isEmpty()) {
            return '<div>No items found</div>';
        }

        $html = '<table>';
        $html .= '<tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr>';

        foreach ($items as $item) {
            $itemTotal = $item->price * $item->quantity;
            $html .= "<tr>";
            $html .= "<td>{$item->product_name}</td>";
            $html .= "<td class='center'>{$item->quantity}</td>";
            $html .= "<td class='right'>\${$item->price}</td>";
            $html .= "<td class='right'>\${$itemTotal}</td>";
            $html .= "</tr>";
        }

        $html .= '</table>';
        return $html;
    }

    /**
     * Default receipt for any other payment method
     */
    private function getDefaultReceiptHtml(MerchantOrder $order, $customer): string
    {
        $merchant = $order->merchant;
        $items = $order->items;
        $subtotal = $order->total_amount;
        $tax = $order->tax_amount;
        $total = $subtotal + $tax;

        $itemsHtml = $this->generateItemsTableHtml($items);
        $statusBadge = $this->getStatusBadge($order->status);
        $paymentMethodLabel = $this->getPaymentMethodLabel($order->payment_method);
        $customerName = $customer ? ($customer->first_name . ' ' . $customer->last_name) : 'N/A';

        return <<<HTML
                <html>
                <head>
                    <style>
                        body { font-family: monospace; font-size: 12px; margin: 0; padding: 10px; }
                        .receipt { max-width: 300px; margin: 0 auto; }
                        .header { text-align: center; margin-bottom: 15px; }
                        .separator { border-top: 1px dashed #000; margin: 10px 0; }
                        .separator-solid { border-top: 1px solid #000; margin: 10px 0; }
                        .bold { font-weight: bold; }
                        .right { text-align: right; }
                        .left { text-align: left; }
                        .center { text-align: center; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { padding: 5px 0; text-align: left; }
                        .total-line { font-weight: bold; border-top: 1px solid #000; margin-top: 5px; padding-top: 5px; }
                        .footer { text-align: center; margin-top: 20px; font-size: 10px; }
                        .badge-success { color: green; }
                        .badge-warning { color: orange; }
                        .badge-danger { color: red; }
                        .badge-secondary { color: gray; }
                    </style>
                </head>
                <body>
                    <div class="receipt">
                        <div class="header">
                            <div class="bold">{$merchant->name}</div>
                            <div class="small">Order Receipt</div>
                            <div class="separator-solid"></div>
                            <div>Date: {$order->created_at->format('m/d/Y')}</div>
                        </div>

                        <div class="customer-info">
                            <div><strong>Customer:</strong> {$customerName}</div>
                            <div><strong>Order #:</strong> {$order->order_number}</div>
                            <div class="separator"></div>
                        </div>

                        {$itemsHtml}

                        <div class="separator"></div>
                        
                        <div>
                            <div><strong>Subtotal:</strong> <span class="right">\${$subtotal}</span></div>
                            <div><strong>Tax:</strong> <span class="right">\${$tax}</span></div>
                            <div class="total-line"><strong>Grand Total:</strong> <span class="right">\${$total}</span></div>
                        </div>

                        <div class="separator"></div>
                        
                        <div>
                            <div><strong>Payment Method:</strong> {$paymentMethodLabel}</div>
                            <div><strong>Status:</strong> {$statusBadge}</div>
                            <div><strong>Payment Status:</strong> Paid</div>
                        </div>

                        <div class="separator"></div>
                        
                        <div class="footer">
                            <div>** CUSTOMER COPY **</div>
                            <div class="separator"></div>
                            <div>Thank you for your business!</div>
                        </div>
                    </div>
                </body>
                </html>
                HTML;
    }
}
