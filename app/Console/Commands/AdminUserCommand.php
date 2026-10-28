<?php

namespace App\Console\Commands;

use App\Core\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Macht ein vorhandenes Konto zum Admin (Passwort bleibt) oder legt ein neues an und zeigt dessen Passwort einmalig an.
 * Ohne Rückfragen, damit es per SSH (scripts/hostinger/create-admin.cmd) läuft.
 */
class AdminUserCommand extends Command
{
    protected $signature = 'mgc:admin {email} {--name=} {--editor : Rolle Redaktion statt Admin} {--reset-password : Bei vorhandenem Konto ein neues Passwort setzen}';

    protected $description = 'Konto zum Admin machen (vorhandenes Passwort bleibt) bzw. neues Admin-Konto anlegen';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Keine gültige E-Mail-Adresse: '.$email);

            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['email' => $email]);
        $existed = $user->exists;
        // Neues Passwort nur für neue Konten oder auf ausdrücklichen Wunsch – sonst bleibt das selbst gewählte
        $password = ! $existed || $this->option('reset-password') ? Str::password(16, symbols: false) : null;

        $user->name = $this->option('name') ?: ($user->name ?: Str::before($email, '@'));
        $user->username ??= User::makeUsername($user->name);
        if ($password) {
            $user->password = $password;
        }
        $user->role = $this->option('editor') ? Role::Editor : Role::Admin;
        $user->email_verified_at ??= now();
        $user->save();

        $this->info(($existed ? 'Vorhandenes Konto' : 'Neues Konto').' '.$email.' hat jetzt die Rolle '.$user->role->value.'.');
        if ($password) {
            $this->line('  Passwort: '.$password);
            $this->warn('  Passwort jetzt sicher notieren – es wird nicht gespeichert und nicht erneut angezeigt.');
        } else {
            $this->line('  Passwort unverändert (neues Passwort: Befehl mit --reset-password).');
        }
        $this->line('  Anmelden: '.rtrim((string) config('app.url'), '/').'/anmelden');

        return self::SUCCESS;
    }
}
