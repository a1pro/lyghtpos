<?php

namespace App\Mail\Pax\Customer;

use App\Models\MerchantOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCompleted extends Mailable
{
    use Queueable, SerializesModels;

    public MerchantOrder $order;
    public string $receiptHtml;

    /**
     * Create a new message instance.
     */
    public function __construct(MerchantOrder $order, string $receiptHtml)
    {
        $this->order = $order;
        $this->receiptHtml = $receiptHtml;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Completed #' . $this->order->order_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.pax.customer.order-completed',
            with: [
                'order' => $this->order,
                'receiptHtml' => $this->receiptHtml,
                'customerName' => $this->order->customer()->first_name ?? $this->order->customer()->name ?? 'Customer',
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
