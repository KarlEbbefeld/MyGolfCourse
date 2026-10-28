<?php

namespace App\Modules\Accounts\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * E-Mail-Bestätigung: Link aus der Mail öffnen bzw. neuen Link anfordern.
 */
class EmailVerificationController extends Controller
{
    /** Ziel des signierten Links aus der Mail (prüft Signatur, Ablauf und angemeldetes Konto). */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->route('account.show')->with('status', 'Danke! Deine E-Mail-Adresse ist bestätigt.');
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return back()->with('status', 'Deine E-Mail-Adresse ist bereits bestätigt.');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Neuer Bestätigungslink ist unterwegs. Schau auch im Spam-Ordner nach.');
    }

    /** Laravel leitet unbestätigte Nutzer bei „verified“-Routen hierher. */
    public function notice(): RedirectResponse
    {
        return redirect()->route('account.show')->with('status', 'Bitte bestätige zuerst deine E-Mail-Adresse.');
    }
}
