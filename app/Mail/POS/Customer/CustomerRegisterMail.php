<?php

namespace App\Mail\POS\Customer;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerRegisterMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $otp;
    public string $validity;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $otp, string $validity)
    {
        $this->user = $user;
        $this->otp = $otp;
        $this->validity = $validity;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Lyght POS - Your OTP Code',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.pos.customer.customer-register-mail',
            with: [
                'user' => $this->user,
                'otp' => $this->otp,
                'validity' => $this->validity,
                'appName' => 'Lyght POS',
                'appUrl' => config('app.url'),
                'currentYear' => now()->year,
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