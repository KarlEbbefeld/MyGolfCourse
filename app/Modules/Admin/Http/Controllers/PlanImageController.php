<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Models\Hole;
use App\Modules\Hazards\Models\PointOfInterest;
use App\Modules\Media\MediaStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Bahngrafik des Clubs (Modus „Plan“). Hindernisse werden im Bahn-Editor per Klick auf die Grafik platziert.
 */
class PlanImageController extends Controller
{
    public function store(Request $request, Hole $hole): RedirectResponse
    {
        $request->validate([
            'plan' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ], [
            'plan.required' => 'Bitte eine Bahngrafik (JPG, PNG oder WebP) auswählen.',
        ]);

        MediaStore::delete($hole->plan_image_url);
        $path = MediaStore::store($request->file('plan'), MediaStore::holeDirectory($hole), 'bahngrafik');
        $hole->update(['plan_image_url' => $path]);

        return redirect()->route('admin.holes.edit', $hole)
            ->with('status', 'Bahngrafik gespeichert. Jetzt bei jedem Hindernis „Auf Grafik setzen“ wählen und auf die Grafik klicken.');
    }

    public function destroy(Hole $hole): RedirectResponse
    {
        MediaStore::delete($hole->plan_image_url);
        $hole->update(['plan_image_url' => null]);
        PointOfInterest::query()->where('hole_id', $hole->id)->update(['plan_x' => null, 'plan_y' => null]);

        return redirect()->route('admin.holes.edit', $hole)->with('status', 'Bahngrafik entfernt.');
    }
}
