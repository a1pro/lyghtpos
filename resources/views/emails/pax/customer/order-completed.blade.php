<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Completed #{{ $order->order_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #fff; border-radius: 5px; padding: 20px;">
        <h1 style="color: #4CAF50;">Order Completed Successfully!</h1>

        <p>Dear <strong>{{ $customerName }}</strong>,</p>

        <p>Thank you for your purchase. Your order has been completed successfully.</p>

        <h2>Order Summary</h2>
        <p>
            <strong>Order Number:</strong> #{{ $order->order_number }}<br>
            <strong>Order Date:</strong> {{ $order->created_at->format('F j, Y g:i A') }}<br>
            <strong>Payment Method:</strong> {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}<br>
            <strong>Order Status:</strong> {{ ucfirst($order->status) }}
        </p>

        <p>
            <strong>Subtotal:</strong> ${{ number_format($order->total_amount, 2) }}<br>
            <strong>Tax:</strong> ${{ number_format($order->tax_amount, 2) }}<br>
            <strong>Total:</strong> ${{ number_format($order->total_amount + $order->tax_amount, 2) }}
        </p>

        @if($order->payment_method === 'hybrid' && $order->card_amount && $order->token_amount)
        <h3>Payment Breakdown</h3>
        <ul>
            <li><strong>Card Payment:</strong> ${{ number_format($order->card_amount + $order->tax_amount, 2) }} (including tax)</li>
            <li><strong>Token Payment:</strong> {{ number_format($order->token_amount, 2) }} tokens</li>
        </ul>
        @endif

        <h2>Transaction Receipt</h2>
        <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #4CAF50; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px;">
            {!! $receiptHtml !!}
        </div>

        <p>If you have any questions about your order, please don't hesitate to contact us.</p>

        <p>Thank you for shopping with us!</p>
    </div>
</body>
</html>
