<?php

namespace App\Mail\POS\Customer;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ForgotPasswordOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $otp;
    public $appName;
    public $appUrl;
    public $currentYear;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, $otp)
    {
        $this->user = $user;
        $this->otp = $otp;

        $this->appName = config('app.name');
        $this->appUrl = config('app.url');
        $this->currentYear = Carbon::now()->year;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password Reset OTP - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.pos.customer.forgot-password-otp-mail',
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
