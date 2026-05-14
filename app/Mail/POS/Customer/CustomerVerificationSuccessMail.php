<?php

namespace App\Mail\POS\Customer;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerVerificationSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $verificationTime;
    public $appName;
    public $appUrl;
    public $currentYear;


    /**
     * Create a new message instance.
     */
    public function __construct($user, $verificationTime)
    {
        $this->user = $user;
        $this->verificationTime = $verificationTime;
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
            subject: 'Email Verified Successfully - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.pos.customer.verification-success',
            with: [
                'user' => $this->user,
                'verificationTime' => $this->verificationTime,
                'appName' => $this->appName,
                'appUrl' => $this->appUrl,
                'currentYear' => $this->currentYear
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
