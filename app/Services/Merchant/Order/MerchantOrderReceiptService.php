<?php

namespace App\Services\Merchant\Order;

use App\Models\MerchantOrder;
use Illuminate\Support\Facades\Log;

class MerchantOrderReceiptService
{
    /**
     * Generate HTML receipt for a completed order following Handpoint format
     * 
     * @param MerchantOrder $order
     * @param bool $forPaxTerminal - true for PAX terminal (no CSS), false for web view (with CSS)
     */
    public function generateReceiptHtml(MerchantOrder $order, bool $forPaxTerminal = false): string
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
        $html = $this->getReceiptHtmlByMethod($order, $customer, $forPaxTerminal);

        return $html;
    }

    /**
     * Get receipt HTML based on payment method
     */
    private function getReceiptHtmlByMethod(MerchantOrder $order, $customer, bool $forPaxTerminal): string
    {
        $method = $order->payment_method;

        switch ($method) {
            case 'card':
                return $this->getCardReceiptHtml($order, $customer, $forPaxTerminal);
            case 'cyclos_tokens':
                return $this->getTokenReceiptHtml($order, $customer, $forPaxTerminal);
            case 'hybrid':
                return $this->getHybridReceiptHtml($order, $customer, $forPaxTerminal);
            case 'cash':
                return $this->getCashReceiptHtml($order, $customer, $forPaxTerminal);
            case 'pos_mobile':
                return $this->getPosMobileReceiptHtml($order, $customer, $forPaxTerminal);
            default:
                return $this->getDefaultReceiptHtml($order, $customer, $forPaxTerminal);
        }
    }

    /**
     * Get CSS styles for web view (only if not for PAX terminal)
     */
    private function getStyles(bool $forPaxTerminal): string
    {
        if ($forPaxTerminal) {
            return ''; // No styles for PAX terminal
        }

        return <<<CSS
            <style>
                body {
                    font-family: 'Courier New', monospace;
                    max-width: 400px;
                    margin: 0 auto;
                    padding: 20px;
                    background: #f5f5f5;
                }
                .receipt {
                    background: white;
                    padding: 20px;
                    border-radius: 10px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .header { text-align: center; margin-bottom: 15px; }
                .separator { border-top: 1px dashed #999; margin: 10px 0; }
                .separator-solid { border-top: 2px solid #333; margin: 10px 0; }
                .separator-dotted { border-top: 1px dotted #999; margin: 10px 0; }
                .bold { font-weight: bold; }
                .right { text-align: right; }
                .left { text-align: left; }
                .center { text-align: center; }
                .large { font-size: 14px; }
                .small { font-size: 10px; }
                .customer-info, .order-info { margin-bottom: 10px; }
                .footer { text-align: center; margin-top: 20px; font-size: 10px; }
                @media print {
                    body { background: white; padding: 0; }
                    .receipt { box-shadow: none; padding: 0; }
                }
            </style>
        CSS;
    }

    /**
     * Receipt for card payments - Handpoint format with EMV details
     */
    private function getCardReceiptHtml(MerchantOrder $order, $customer, bool $forPaxTerminal): string
    {
        $merchant = $order->merchant;
        $items = $order->items;
        $subtotal = $order->total_amount;
        $tax = $order->tax_amount;
        $finStatus = $order->handpoint_fin_status;
        $total = $subtotal + $tax;
        $transactionId = $order->card_transaction_id ?? 'N/A';
        $customerName = $customer ? ($customer->first_name . ' ' . $customer->last_name) : 'N/A';
        $styles = $this->getStyles($forPaxTerminal);

        $itemsHtml = '';
        foreach ($items as $item) {
            $itemTotal = $item->price * $item->quantity;
            $itemsHtml .= "<div><label class=\"left\">{$item->quantity}x {$item->product_name}</label><span class=\"right\">\${$itemTotal}</span></div>";
        }

        // Build EMV details section only if any EMV data exists
        $emvHtml = $this->buildEmvDetailsHtml($order);

        return <<<HTML
        <html>
        <head>{$styles}</head>
        <body>
            <div class="receipt">
                <header>
                    <div id="merchant_name">
                        <p class="large bold center">{$merchant->first_name} {$merchant->last_name}</p>
                            <p class="large bold center">{$merchant->address->full_address}</p>
                    </div>
                    <div class="separator solid" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                    <div>
                        <label class="left">{$order->created_at->format('m/d/Y')}</label>
                        <span class="right">{$order->created_at->format('g:i A')}</span>
                    </div>
                </header>
                <main>
                    <div id="response">
                        <p class="large bold center">APPROVED</p>
                    </div>
                    <div id="txn_type">
                        <p class="large bold center">CARD PAYMENT</p>
                    </div>
                    <div id="finstatus">
                        <label class="left bold">SALE: </label>
                        <span class="large right bold">{$finStatus}</span>
                    </div>
                    <div id="amount">
                        <label class="left bold">Amount: </label>
                        <span class="large right bold">\${$total}</span>
                    </div>
                    <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                    
                    <div id="order_info">
                        <p class="left bold">Order #: {$order->order_number}</p>
                        <p class="left bold">Customer: {$customerName}</p>
                    </div>
                    
                    <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                    
                    <div id="items">
                        <p class="left bold">Items:</p>
                        {$itemsHtml}
                    </div>
                    
                    <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                    
                    <div id="totals">
                        <div><label class="left">Subtotal:</label><span class="right">\${$subtotal}</span></div>
                        <div><label class="left">Tax:</label><span class="right">\${$tax}</span></div>
                        <div class="separator dotted" style="margin-top: 5px; margin-bottom: 5px;"></div>
                        <div><label class="left bold">TOTAL:</label><span class="right bold">\${$total}</span></div>
                    </div>
                    
                    <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                    
                    <div id="payment_details">
                        <div><label class="left bold">Payment Method:</label><span class="right">Card</span></div>
                        <div><label class="left bold">Transaction ID:</label><span class="right">{$transactionId}</span></div>
                    </div>
                    
                    {$emvHtml}
                    
                    <div class="separator solid" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                    
                    <div id="receipt_type_description">
                        <p class="center bold">** CUSTOMER COPY **</p>
                    </div>
                </main>
                <footer>
                    <div class="separator" style="margin-top: 20px; margin-bottom: 20px;"></div>
                    <div id="thankyou">
                        <p class="center">Thank you for your business!</p>
                    </div>
                    <div class="separator solid" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                </footer>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Receipt for token payments - Handpoint format
     */
    private function getTokenReceiptHtml(MerchantOrder $order, $customer, bool $forPaxTerminal): string
    {
        $merchant = $order->merchant;
        $items = $order->items;
        $subtotal = $order->total_amount;
        $tax = $order->tax_amount;
        $total = $subtotal + $tax;
        $tokenAmount = $order->token_amount ?? $total;
        $cyclosTxId = $order->cyclos_transaction_id ?? 'N/A';
        $customerName = $customer ? ($customer->first_name . ' ' . $customer->last_name) : 'N/A';
        $styles = $this->getStyles($forPaxTerminal);

        $itemsHtml = '';
        foreach ($items as $item) {
            $itemTotal = $item->price * $item->quantity;
            $itemsHtml .= "<div><label class=\"left\">{$item->quantity}x {$item->product_name}</label><span class=\"right\">\${$itemTotal}</span></div>";
        }

        return <<<HTML
            <html>
            <head>{$styles}</head>
            <body>
                <div class="receipt">
                    <header>
                        <div id="merchant_name">
                            <p class="large bold center">{$merchant->first_name} {$merchant->last_name}</p>
                            <p class="large bold center">{$merchant->address->full_address}</p>
                        </div>
                        <div class="separator solid" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        <div>
                            <label class="left">{$order->created_at->format('m/d/Y')}</label>
                            <span class="right">{$order->created_at->format('g:i A')}</span>
                        </div>
                    </header>
                    <main>
                        <div id="response">
                            <p class="large bold center">TOKEN PAYMENT SUCCESSFUL</p>
                        </div>
                        <div id="txn_type">
                            <p class="large bold center">TOKEN PAYMENT</p>
                        </div>
                        <div id="amount">
                            <label class="left bold">Amount: </label>
                            <span class="large right bold">\${$total}</span>
                        </div>
                        <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        
                        <div id="order_info">
                            <p class="left bold">Order #: {$order->order_number}</p>
                            <p class="left bold">Customer: {$customerName}</p>
                        </div>
                        
                        <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        
                        <div id="items">
                            <p class="left bold">Items:</p>
                            {$itemsHtml}
                        </div>
                        
                        <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        
                        <div id="totals">
                            <div><label class="left">Subtotal:</label><span class="right">\${$subtotal}</span></div>
                            <div><label class="left">Tax:</label><span class="right">\${$tax}</span></div>
                            <div><label class="left">Tokens Used:</label><span class="right">{$tokenAmount}</span></div>
                            <div class="separator dotted" style="margin-top: 5px; margin-bottom: 5px;"></div>
                            <div><label class="left bold">TOTAL:</label><span class="right bold">\${$total}</span></div>
                        </div>
                        
                        <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        
                        <div id="payment_details">
                            <div><label class="left bold">Payment Method:</label><span class="right">Cyclos Tokens</span></div>
                            <div><label class="left bold">Transaction ID:</label><span class="right">{$cyclosTxId}</span></div>
                        </div>
                        
                        <div class="separator solid" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        
                        <div id="wallet_info">
                            <p class="center small">Token payment processed from your Cyclos wallet</p>
                        </div>
                        
                        <div id="receipt_type_description">
                            <p class="center bold">** CUSTOMER COPY **</p>
                        </div>
                    </main>
                    <footer>
                        <div class="separator" style="margin-top: 20px; margin-bottom: 20px;"></div>
                        <div id="thankyou">
                            <p class="center">Thank you for your business!</p>
                        </div>
                        <div class="separator solid" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                    </footer>
                </div>
            </body>
            </html>
            HTML;
    }

    /**
     * Receipt for hybrid payments - Handpoint format with EMV details
     */
    private function getHybridReceiptHtml(MerchantOrder $order, $customer, bool $forPaxTerminal): string
    {
        $merchant = $order->merchant;
        $items = $order->items;
        $subtotal = $order->total_amount;
        $tax = $order->tax_amount;
        $finStatus = $order->handpoint_fin_status;
        $total = $subtotal + $tax;
        $cardAmount = $order->card_amount;
        $tokenAmount = $order->token_amount;
        $cardTxId = $order->card_transaction_id ?? 'N/A';
        $cyclosTxId = $order->cyclos_transaction_id ?? 'N/A';
        $cardTotalWithTax = $cardAmount + $tax;
        $customerName = $customer ? ($customer->first_name . ' ' . $customer->last_name) : 'N/A';
        $styles = $this->getStyles($forPaxTerminal);

        $itemsHtml = '';
        foreach ($items as $item) {
            $itemTotal = $item->price * $item->quantity;
            $itemsHtml .= "<div><label class=\"left\">{$item->quantity}x {$item->product_name}</label><span class=\"right\">\${$itemTotal}</span></div>";
        }

        // Build EMV details section only if any EMV data exists
        $emvHtml = $this->buildEmvDetailsHtml($order);

        return <<<HTML
        <html>
        <head>{$styles}</head>
        <body>
            <div class="receipt">
                <header>
                    <div id="merchant_name">
                        <p class="large bold center">{$merchant->first_name} {$merchant->last_name}</p>
                            <p class="large bold center">{$merchant->address->full_address}</p>
                    </div>
                    <div class="separator solid" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                    <div>
                        <label class="left">{$order->created_at->format('m/d/Y')}</label>
                        <span class="right">{$order->created_at->format('g:i A')}</span>
                    </div>
                </header>
                <main>
                    <div id="response">
                        <p class="large bold center">HYBRID PAYMENT SUCCESSFUL</p>
                    </div>
                    <div id="txn_type">
                        <p class="large bold center">SPLIT PAYMENT</p>
                    </div>
                    <div id="finstatus">
                        <label class="left bold">SALE: </label>
                        <span class="large right bold">{$finStatus}</span>
                    </div>
                    <div id="amount">
                        <label class="left bold">Total Amount: </label>
                        <span class="large right bold">\${$total}</span>
                    </div>
                    <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                    
                    <div id="order_info">
                        <p class="left bold">Order #: {$order->order_number}</p>
                        <p class="left bold">Customer: {$customerName}</p>
                    </div>
                    
                    <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                    
                    <div id="items">
                        <p class="left bold">Items:</p>
                        {$itemsHtml}
                    </div>
                    
                    <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                    
                    <div id="totals">
                        <div><label class="left">Subtotal:</label><span class="right">\${$subtotal}</span></div>
                        <div><label class="left">Tax:</label><span class="right">\${$tax}</span></div>
                        <div class="separator dotted" style="margin-top: 5px; margin-bottom: 5px;"></div>
                        <div><label class="left bold">TOTAL:</label><span class="right bold">\${$total}</span></div>
                    </div>
                    
                    <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                    
                    <div id="payment_breakdown">
                        <p class="left bold">Payment Breakdown:</p>
                        <div><label class="left">Card Payment:</label><span class="right">\${$cardTotalWithTax} (incl. tax)</span></div>
                        <div><label class="left">Token Payment:</label><span class="right">{$tokenAmount} (excl. tax)</span></div>
                    </div>
                    
                    <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                    
                    <div id="payment_details">
                        <div><label class="left bold">Payment Method:</label><span class="right">Hybrid (Card + Tokens)</span></div>
                        <div><label class="left bold">Card TXN ID:</label><span class="right">{$cardTxId}</span></div>
                        <div><label class="left bold">Token TXN ID:</label><span class="right">{$cyclosTxId}</span></div>
                    </div>
                    
                    {$emvHtml}
                    
                    <div class="separator solid" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                    
                    <div id="receipt_type_description">
                        <p class="center bold">** CUSTOMER COPY **</p>
                    </div>
                </main>
                <footer>
                    <div class="separator" style="margin-top: 20px; margin-bottom: 20px;"></div>
                    <div id="thankyou">
                        <p class="center">Thank you for your business!</p>
                    </div>
                    <div class="separator solid" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                </footer>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Receipt for cash payments - Handpoint format
     */
    private function getCashReceiptHtml(MerchantOrder $order, $customer, bool $forPaxTerminal): string
    {
        $merchant = $order->merchant;
        $items = $order->items;
        $subtotal = $order->total_amount;
        $tax = $order->tax_amount;
        $total = $subtotal + $tax;
        $customerName = $customer ? ($customer->first_name . ' ' . $customer->last_name) : 'N/A';
        $styles = $this->getStyles($forPaxTerminal);

        $itemsHtml = '';
        foreach ($items as $item) {
            $itemTotal = $item->price * $item->quantity;
            $itemsHtml .= "<div><label class=\"left\">{$item->quantity}x {$item->product_name}</label><span class=\"right\">\${$itemTotal}</span></div>";
        }

        return <<<HTML
            <html>
            <head>{$styles}</head>
            <body>
                <div class="receipt">
                    <header>
                        <div id="merchant_name">
                            <p class="large bold center">{$merchant->first_name} {$merchant->last_name}</p>
                            <p class="large bold center">{$merchant->address->full_address}</p>
                        </div>
                        <div class="separator solid" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        <div>
                            <label class="left">{$order->created_at->format('m/d/Y')}</label>
                            <span class="right">{$order->created_at->format('g:i A')}</span>
                        </div>
                    </header>
                    <main>
                        <div id="response">
                            <p class="large bold center">CASH PAYMENT SUCCESSFUL</p>
                        </div>
                        <div id="txn_type">
                            <p class="large bold center">CASH SALE</p>
                        </div>
                        <div id="amount">
                            <label class="left bold">Amount Paid: </label>
                            <span class="large right bold">\${$total}</span>
                        </div>
                        <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        
                        <div id="order_info">
                            <p class="left bold">Order #: {$order->order_number}</p>
                            <p class="left bold">Customer: {$customerName}</p>
                        </div>
                        
                        <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        
                        <div id="items">
                            <p class="left bold">Items:</p>
                            {$itemsHtml}
                        </div>
                        
                        <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        
                        <div id="totals">
                            <div><label class="left">Subtotal:</label><span class="right">\${$subtotal}</span></div>
                            <div><label class="left">Tax:</label><span class="right">\${$tax}</span></div>
                            <div class="separator dotted" style="margin-top: 5px; margin-bottom: 5px;"></div>
                            <div><label class="left bold">TOTAL:</label><span class="right bold">\${$total}</span></div>
                        </div>
                        
                        <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        
                        <div id="payment_details">
                            <div><label class="left bold">Payment Method:</label><span class="right">Cash</span></div>
                            <div><label class="left bold">Amount Tendered:</label><span class="right">\${$total}</span></div>
                        </div>
                        
                        <div class="separator solid" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        
                        <div id="receipt_type_description">
                            <p class="center bold">** CUSTOMER COPY **</p>
                        </div>
                    </main>
                    <footer>
                        <div class="separator" style="margin-top: 20px; margin-bottom: 20px;"></div>
                        <div id="thankyou">
                            <p class="center">Thank you for your business!</p>
                        </div>
                        <div class="separator solid" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                    </footer>
                </div>
            </body>
            </html>
            HTML;
    }

    /**
     * Receipt for POS mobile payments - Handpoint format
     */
    private function getPosMobileReceiptHtml(MerchantOrder $order, $customer, bool $forPaxTerminal): string
    {
        $merchant = $order->merchant;
        $items = $order->items;
        $subtotal = $order->total_amount;
        $tax = $order->tax_amount;
        $total = $subtotal + $tax;
        $customerName = $customer ? ($customer->first_name . ' ' . $customer->last_name) : 'N/A';
        $styles = $this->getStyles($forPaxTerminal);

        $itemsHtml = '';
        foreach ($items as $item) {
            $itemTotal = $item->price * $item->quantity;
            $itemsHtml .= "<div><label class=\"left\">{$item->quantity}x {$item->product_name}</label><span class=\"right\">\${$itemTotal}</span></div>";
        }

        return <<<HTML
            <html>
            <head>{$styles}</head>
            <body>
                <div class="receipt">
                    <header>
                        <div id="merchant_name">
                            <p class="large bold center">{$merchant->first_name} {$merchant->last_name}</p>
                            <p class="large bold center">{$merchant->address->full_address}</p>
                        </div>
                        <div class="separator solid" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        <div>
                            <label class="left">{$order->created_at->format('m/d/Y')}</label>
                            <span class="right">{$order->created_at->format('g:i A')}</span>
                        </div>
                    </header>
                    <main>
                        <div id="response">
                            <p class="large bold center">ORDER COMPLETED</p>
                        </div>
                        <div id="txn_type">
                            <p class="large bold center">POS MOBILE ORDER</p>
                        </div>
                        <div id="amount">
                            <label class="left bold">Total: </label>
                            <span class="large right bold">\${$total}</span>
                        </div>
                        <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        
                        <div id="order_info">
                            <p class="left bold">Order #: {$order->order_number}</p>
                            <p class="left bold">Customer: {$customerName}</p>
                        </div>
                        
                        <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        
                        <div id="items">
                            <p class="left bold">Items:</p>
                            {$itemsHtml}
                        </div>
                        
                        <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        
                        <div id="totals">
                            <div><label class="left">Subtotal:</label><span class="right">\${$subtotal}</span></div>
                            <div><label class="left">Tax:</label><span class="right">\${$tax}</span></div>
                            <div class="separator dotted" style="margin-top: 5px; margin-bottom: 5px;"></div>
                            <div><label class="left bold">TOTAL:</label><span class="right bold">\${$total}</span></div>
                        </div>
                        
                        <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        
                        <div id="payment_details">
                            <div><label class="left bold">Payment Method:</label><span class="right">POS Mobile</span></div>
                            <div><label class="left bold">Processor:</label><span class="right">POS Lyght Mobile App</span></div>
                        </div>
                        
                        <div class="separator solid" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        
                        <div id="receipt_type_description">
                            <p class="center bold">** CUSTOMER COPY **</p>
                        </div>
                    </main>
                    <footer>
                        <div class="separator" style="margin-top: 20px; margin-bottom: 20px;"></div>
                        <div id="thankyou">
                            <p class="center">Thank you for your business!</p>
                        </div>
                        <div class="separator solid" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                    </footer>
                </div>
            </body>
            </html>
            HTML;
    }

    /**
     * Default receipt for any other payment method - Handpoint format
     */
    private function getDefaultReceiptHtml(MerchantOrder $order, $customer, bool $forPaxTerminal): string
    {
        $merchant = $order->merchant;
        $items = $order->items;
        $subtotal = $order->total_amount;
        $tax = $order->tax_amount;
        $total = $subtotal + $tax;
        $paymentMethodLabel = $this->getPaymentMethodLabel($order->payment_method);
        $customerName = $customer ? ($customer->first_name . ' ' . $customer->last_name) : 'N/A';
        $styles = $this->getStyles($forPaxTerminal);

        $itemsHtml = '';
        foreach ($items as $item) {
            $itemTotal = $item->price * $item->quantity;
            $itemsHtml .= "<div><label class=\"left\">{$item->quantity}x {$item->product_name}</label><span class=\"right\">\${$itemTotal}</span></div>";
        }

        return <<<HTML
            <html>
            <head>{$styles}</head>
            <body>
                <div class="receipt">
                    <header>
                        <div id="merchant_name">
                            <p class="large bold center">{$merchant->first_name} {$merchant->last_name}</p>
                            <p class="large bold center">{$merchant->address->full_address}</p>
                        </div>
                        <div class="separator solid" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        <div>
                            <label class="left">{$order->created_at->format('m/d/Y')}</label>
                            <span class="right">{$order->created_at->format('g:i A')}</span>
                        </div>
                    </header>
                    <main>
                        <div id="response">
                            <p class="large bold center">ORDER COMPLETED</p>
                        </div>
                        <div id="txn_type">
                            <p class="large bold center">SALE</p>
                        </div>
                        <div id="amount">
                            <label class="left bold">Total: </label>
                            <span class="large right bold">\${$total}</span>
                        </div>
                        <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        
                        <div id="order_info">
                            <p class="left bold">Order #: {$order->order_number}</p>
                            <p class="left bold">Customer: {$customerName}</p>
                        </div>
                        
                        <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        
                        <div id="items">
                            <p class="left bold">Items:</p>
                            {$itemsHtml}
                        </div>
                        
                        <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        
                        <div id="totals">
                            <div><label class="left">Subtotal:</label><span class="right">\${$subtotal}</span></div>
                            <div><label class="left">Tax:</label><span class="right">\${$tax}</span></div>
                            <div class="separator dotted" style="margin-top: 5px; margin-bottom: 5px;"></div>
                            <div><label class="left bold">TOTAL:</label><span class="right bold">\${$total}</span></div>
                        </div>
                        
                        <div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        
                        <div id="payment_details">
                            <div><label class="left bold">Payment Method:</label><span class="right">{$paymentMethodLabel}</span></div>
                        </div>
                        
                        <div class="separator solid" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                        
                        <div id="receipt_type_description">
                            <p class="center bold">** CUSTOMER COPY **</p>
                        </div>
                    </main>
                    <footer>
                        <div class="separator" style="margin-top: 20px; margin-bottom: 20px;"></div>
                        <div id="thankyou">
                            <p class="center">Thank you for your business!</p>
                        </div>
                        <div class="separator solid" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>
                    </footer>
                </div>
            </body>
            </html>
            HTML;
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
     * Build EMV details HTML section for receipt
     */
    private function buildEmvDetailsHtml(MerchantOrder $order): string
    {
        // Check if any EMV data exists
        $hasEmvData = $order->handpoint_transaction_reference
            || $order->handpoint_rrn
            || $order->handpoint_aid
            || $order->handpoint_arc;

        if (!$hasEmvData) {
            return '';
        }

        $html = '<div class="separator dotted" style="margin-top: 10px; height: 2px; margin-bottom: 10px;"></div>';
        $html .= '<div id="emv_details">';

        // Transaction Reference
        if ($order->handpoint_transaction_reference) {
            $html .= '<div>';
            $html .= '<label class="left bold">Transaction Reference:</label>';
            $html .= '<span class="right">' . htmlspecialchars($order->handpoint_transaction_reference) . '</span>';
            $html .= '</div>';
        }

        // RRN (Retrieval Reference Number)
        if ($order->handpoint_rrn) {
            $html .= '<div>';
            $html .= '<label class="left bold">RRN:</label>';
            $html .= '<span class="right">' . htmlspecialchars($order->handpoint_rrn) . '</span>';
            $html .= '</div>';
        }

        $html .= '<div class="separator dotted" style="margin-top: 5px; margin-bottom: 5px;"></div>';

        $html .= '<label class="left bold">EMV Details:</label>';

        // AID (Application Identifier)
        if ($order->handpoint_aid) {
            $html .= '<div>';
            $html .= '<label class="left bold">AID:</label>';
            $html .= '<span class="right">' . htmlspecialchars($order->handpoint_aid) . '</span>';
            $html .= '</div>';
        }

        // ARC (Authorization Response Code)
        if ($order->handpoint_arc) {
            $html .= '<div>';
            $html .= '<label class="left bold">ARC:</label>';
            $html .= '<span class="right">' . htmlspecialchars($order->handpoint_arc) . '</span>';
            $html .= '</div>';
        }

        // Issuer Response Code
        if ($order->handpoint_issuer_response_code) {
            $html .= '<div>';
            $html .= '<label class="left bold">Issuer Response Code:</label>';
            $html .= '<span class="right">' . htmlspecialchars($order->handpoint_issuer_response_code) . '</span>';
            $html .= '</div>';
        }

        // Authorization Code
        if ($order->handpoint_authorization_code) {
            $html .= '<div>';
            $html .= '<label class="left bold">Authorization Code:</label>';
            $html .= '<span class="right">' . htmlspecialchars($order->handpoint_authorization_code) . '</span>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }
}
