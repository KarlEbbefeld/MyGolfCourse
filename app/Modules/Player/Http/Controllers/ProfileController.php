<?php

namespace App\Modules\Player\Http\Controllers;

use App\Core\Support\Units;
use App\Http\Controllers\Controller;
use App\Modules\Player\Support\Bag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Schlägerdistanzen („Mein Bag“) in users.settings. Clubzugehörigkeit: Modul Community (ClubController).
 */
class ProfileController extends Controller
{
    /** Eingabe in der Einheit des Nutzers (m oder yd), gespeichert in Metern. */
    public function bag(Request $request): RedirectResponse
    {
        $rules = [];
        foreach (array_keys(Bag::CLUBS) as $key) {
            $rules['bag.'.$key] = ['nullable', 'numeric', 'between:5,400'];
        }
        $data = $request->validate($rules);

        $factor = Units::current() === 'yd' ? 1 / Units::YARDS_PER_METER : 1;
        $bag = [];
        foreach ((array) ($data['bag'] ?? []) as $key => $value) {
            if (array_key_exists($key, Bag::CLUBS) && is_numeric($value) && $value > 0) {
                $bag[$key] = round((float) $value * $factor, 1);
            }
        }

        $user = $request->user();
        $user->update(['settings' => array_merge($user->settings ?? [], ['bag' => $bag])]);

        return back()->with('status', $bag ? 'Dein Bag ist gespeichert – die Bahnseiten zeigen jetzt deinen Spielplan.' : 'Schlägerdistanzen entfernt.');
    }
}
