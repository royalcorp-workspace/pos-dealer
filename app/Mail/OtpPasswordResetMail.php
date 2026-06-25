<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Markdown;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class OtpPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $email,
        public readonly string $otpCode,
        public readonly int $expiresMinutes
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Kode OTP Reset Password'
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.otp-password-reset',
            with: [
                'otpCode' => $this->otpCode,
                'email' => $this->email,
                'expiresMinutes' => $this->expiresMinutes,
            ]
        );
    }
}

