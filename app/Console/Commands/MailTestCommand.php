<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Testmail verschicken (php artisan mgc:mail-test name@beispiel.de) – zeigt, ob der Mailversand auf dem Server klappt.
 */
class MailTestCommand extends Command
{
    protected $signature = 'mgc:mail-test {email}';

    protected $description = 'Testmail an die angegebene Adresse senden';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $mailer = config('mail.default');

        $this->line('Mailer: '.$mailer.' · Host: '.config('mail.mailers.smtp.host').' · Absender: '.config('mail.from.address'));

        if ($mailer === 'log') {
            $this->warn('MAIL_MAILER=log – Mails landen nur in storage/logs, es wird nichts verschickt.');
        }

        try {
            Mail::raw("Diese Testmail kommt von My Golf Course.\n\nWenn du sie liest, funktionieren Passwort-Reset und E-Mail-Bestätigung.", function ($message) use ($email) {
                $message->to($email)->subject('Testmail von My Golf Course');
            });
        } catch (Throwable $e) {
            $this->error('Versand fehlgeschlagen: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Testmail an '.$email.' übergeben. Posteingang (und Spam) prüfen.');

        return self::SUCCESS;
    }
}
