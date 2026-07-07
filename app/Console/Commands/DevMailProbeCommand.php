<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Mail\MailManager;

class DevMailProbeCommand extends Command
{
    protected $signature = 'dev:mail-probe {to}';
    protected $description = 'Send a test email using current mail config.';

    public function __construct(private readonly MailManager $mail)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $to = (string) $this->argument('to');

        $this->mail->raw('Mail probe OK', function ($message) use ($to) {
            $message->to($to)->subject('Mail probe');
        });

        $this->info('Mail probe sent to: '.$to);
        return self::SUCCESS;
    }
}
