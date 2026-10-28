<?php

namespace App\Modules\Accounts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Core\Support\Units;
use App\Modules\Courses\Enums\TeeColor;
use App\Modules\Courses\Models\Course;
use App\Modules\Community\Models\Message;
use App\Modules\Player\Support\Bag;
use Illuminate\Support\Facades\DB;
use App\Modules\Favorites\Support\RecentlyViewed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Konto: Übersicht (Merkliste, zuletzt angesehen), Einstellungen, Passwort, Datenexport, Löschen.
 */
class AccountController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $rounds = $user->rounds()->with(['course', 'teeSet'])->latest('played_on')->latest('id')->get();
        $fullRounds = $rounds->where('holes_played', 18)->whereNotNull('score');

        return view('accounts.show', [
            'user' => $user,
            'favorites' => $user->favoriteCourses()->with('holes.tees')->get(),
            'recent' => RecentlyViewed::courses(6),
            'homeCourse' => $user->homeCourseId() ? Course::query()->published()->with('holes.tees')->find($user->homeCourseId()) : null,
            'rounds' => $rounds->take(8),
            'stats' => [
                'rounds' => $rounds->count(),
                'courses' => $rounds->pluck('course_id')->unique()->count(),
                'best' => $fullRounds->min('score'),
                'average' => $fullRounds->isNotEmpty() ? round($fullRounds->avg('score'), 1) : null,
            ],
            'notes' => $user->holeNotes()->with('hole.course')->latest('updated_at')->limit(10)->get(),
            'bag' => Bag::for($user),
        ]);
    }

    public function settings(Request $request): View
    {
        $bag = Bag::for($request->user());

        return view('accounts.settings', [
            'user' => $request->user(),
            'teeColors' => TeeColor::cases(),
            'clubs' => Bag::CLUBS,
            // Anzeige in der Einheit des Nutzers
            'bagValues' => array_map(fn ($m) => Units::value($m), $bag->carries),
            'unit' => Units::current(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $request->merge(['username' => Str::lower(trim((string) $request->input('username')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[a-z0-9][a-z0-9-]*$/', Rule::unique('users', 'username')->ignore($user->id)],
            'bio' => ['nullable', 'string', 'max:500'],
            'handicap' => ['nullable', 'numeric', 'between:-10,54'],
            'units' => ['required', Rule::in(User::UNITS)],
            'default_tee_color' => ['nullable', Rule::enum(TeeColor::class)],
        ], [
            'username.regex' => 'Der Nutzername darf nur Kleinbuchstaben, Zahlen und Bindestriche enthalten.',
            'username.unique' => 'Dieser Nutzername ist schon vergeben.',
        ]);

        $emailChanged = $data['email'] !== $user->email;

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => $data['username'],
            'bio' => $data['bio'] ?: null,
            'handicap' => $data['handicap'] !== null && $data['handicap'] !== '' ? round((float) $data['handicap'], 1) : null,
            'profile_public' => $request->boolean('profile_public'),
            'settings' => array_merge($user->settings ?? [], [
                'units' => $data['units'],
                'default_tee_color' => $data['default_tee_color'] ?? null,
            ]),
        ]);
        if ($emailChanged) {
            $user->email_verified_at = null;
        }
        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();

            return back()->with('status', 'Gespeichert. Bitte bestätige deine neue E-Mail-Adresse über den Link, den wir dir geschickt haben.');
        }

        return back()->with('status', 'Einstellungen gespeichert.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.current_password' => 'Das aktuelle Passwort stimmt nicht.',
        ]);

        $request->user()->update(['password' => $request->input('password')]);

        return back()->with('status', 'Passwort geändert.');
    }

    /** Auskunft nach Art. 15 DSGVO: alle gespeicherten Daten als JSON. Neue Module mit Personendaten ergänzen hier. */
    public function export(Request $request): Response
    {
        $user = $request->user();

        $data = [
            'exportiert_am' => now()->toIso8601String(),
            'konto' => $user->only(['name', 'username', 'email', 'bio', 'handicap', 'profile_public', 'club_id', 'locale', 'settings', 'created_at', 'updated_at']),
            'folgt' => $user->following()->pluck('username'),
            'follower' => $user->followers()->pluck('username'),
            'blockiert' => DB::table('blocks')->where('blocker_id', $user->id)->count().' Person(en)',
            'nachrichten' => Message::query()->where('user_id', $user->id)->with('conversation.course')->get()->map(fn ($m) => [
                'chat' => $m->conversation?->isClub() ? 'Club-Chat '.$m->conversation->course?->name : 'Direktnachricht',
                'text' => $m->body,
                'gesendet_am' => $m->created_at?->toIso8601String(),
            ]),
            'merkliste' => $user->favoriteCourses()->get()->map(fn ($c) => ['platz' => $c->name, 'gemerkt_am' => $c->pivot->created_at?->toIso8601String()]),
            'zuletzt_angesehen' => $user->viewedCourses()->get()->map(fn ($c) => ['platz' => $c->name, 'angesehen_am' => $c->pivot->viewed_at]),
            'notizen' => $user->holeNotes()->with('hole.course')->get()->map(fn ($n) => [
                'platz' => $n->hole?->course?->name, 'bahn' => $n->hole?->number, 'notiz' => $n->body, 'geaendert_am' => $n->updated_at?->toIso8601String(),
            ]),
            'runden' => $user->rounds()->with('course')->get()->map(fn ($r) => [
                'platz' => $r->course?->name, 'datum' => $r->played_on?->toDateString(), 'loecher' => $r->holes_played, 'schlaege' => $r->score, 'notiz' => $r->note,
            ]),
        ];

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="my-golf-course-daten.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']], [
            'password.current_password' => 'Das Passwort stimmt nicht – Konto wurde nicht gelöscht.',
        ]);

        $user = $request->user();
        Auth::logout();
        $user->delete(); // Merkliste und Verlauf werden per Fremdschlüssel mitgelöscht

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Dein Konto und alle zugehörigen Daten wurden gelöscht.');
    }
}
