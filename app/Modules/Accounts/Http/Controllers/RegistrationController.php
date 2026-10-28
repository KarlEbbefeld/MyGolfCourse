<?php

namespace App\Modules\Accounts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Registrierung. Die Erkundung der Plätze bleibt ohne Konto möglich; ein Konto braucht man für die Merkliste.
 */
class RegistrationController extends Controller
{
    public function create(): View|RedirectResponse
    {
        return Auth::check() ? redirect()->route('account.show') : view('accounts.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'privacy' => ['accepted'],
        ], [
            'privacy.accepted' => 'Bitte bestätige, dass du die Datenschutzerklärung gelesen hast.',
            'email.unique' => 'Mit dieser E-Mail-Adresse gibt es bereits ein Konto. Melde dich an oder setze dein Passwort zurück.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'username' => User::makeUsername($data['name']),
            'profile_public' => $request->boolean('profile_public'),
            'settings' => ['units' => 'm'],
        ]);

        // Schickt den Bestätigungslink (Laravel: Registered → SendEmailVerificationNotification)
        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('account.show'))
            ->with('status', 'Willkommen, '.$user->name.'! Wir haben dir einen Link zur Bestätigung deiner E-Mail-Adresse geschickt.');
    }
}
