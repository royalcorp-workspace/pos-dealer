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

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $email,
        public readonly string $token
    ) {
    }

    public function (Envelope):
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Verifikasi Email Akun'
        );
    }

    public function (Content):
    {
        return new Content(
            markdown: 'emails.verify-email',
            with: [
                'token' => $this->token,
                'email' => $this->email,
                'verifyUrl' => url("/api/auth/verify-email?token={$this->token}"),
            ]
        );
    }
}
