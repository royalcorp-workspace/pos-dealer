<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class MailEventSubscriber
{
    /**
     * Handle email sending event.
     */
    public function handleSending(MessageSending $event): void
    {
        try {
            $message = $event->message;
            $to = collect($message->getTo() ?? [])->map(fn($addr) => $addr->getAddress())->implode(', ');
            $from = collect($message->getFrom() ?? [])->map(fn($addr) => $addr->getAddress())->implode(', ');
            $subject = (string) $message->getSubject();

            Log::channel('email')->info("[EMAIL_SENDING] To: {$to} | Subject: '{$subject}'", [
                'event' => 'sending',
                'to' => $to,
                'from' => $from,
                'subject' => $subject,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            // Fail silently to never interrupt email sending
        }
    }

    /**
     * Handle email sent event.
     */
    public function handleSent(MessageSent $event): void
    {
        try {
            $sent = $event->sent;
            $symfonySent = method_exists($sent, 'getSymfonySentMessage') 
                ? $sent->getSymfonySentMessage() 
                : $sent;
            $originalMessage = method_exists($symfonySent, 'getOriginalMessage') 
                ? $symfonySent->getOriginalMessage() 
                : (method_exists($sent, 'getOriginalMessage') ? $sent->getOriginalMessage() : null);

            $to = '';
            $from = '';
            $subject = '';

            if ($originalMessage) {
                if (method_exists($originalMessage, 'getTo')) {
                    $to = collect($originalMessage->getTo() ?? [])->map(fn($addr) => $addr->getAddress())->implode(', ');
                }
                if (method_exists($originalMessage, 'getFrom')) {
                    $from = collect($originalMessage->getFrom() ?? [])->map(fn($addr) => $addr->getAddress())->implode(', ');
                }
                if (method_exists($originalMessage, 'getSubject')) {
                    $subject = (string) $originalMessage->getSubject();
                }
            }

            $messageId = method_exists($sent, 'getMessageId') 
                ? $sent->getMessageId() 
                : (method_exists($symfonySent, 'getMessageId') ? $symfonySent->getMessageId() : null);

            Log::channel('email')->info("[EMAIL_SENT] To: {$to} | Subject: '{$subject}' | Message-ID: {$messageId}", [
                'event' => 'sent',
                'to' => $to,
                'from' => $from,
                'subject' => $subject,
                'message_id' => $messageId,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            // Fail silently to never interrupt application flow
        }
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events): array
    {
        return [
            MessageSending::class => 'handleSending',
            MessageSent::class => 'handleSent',
        ];
    }
}
