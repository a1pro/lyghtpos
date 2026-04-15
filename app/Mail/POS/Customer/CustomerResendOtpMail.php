<?php

namespace App\Mail\POS\Customer;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerResendOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $otp;
    public $validity;
    public $appName;
    public $appUrl;
    public $currentYear;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $otp, $validity)
    {
        $this->user = $user;
        $this->otp = $otp;
        $this->validity = $validity;
        $this->appName = config('app.name');
        $this->appUrl = config('app.url');
        $this->currentYear = now()->year;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New OTP Request - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.pos.customer.resend-otp',
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
