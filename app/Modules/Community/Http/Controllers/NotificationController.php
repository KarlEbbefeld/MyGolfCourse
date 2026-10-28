<?php

namespace App\Modules\Community\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Community\Support\Inbox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Mitteilungen (Glocke): werden beim Öffnen der Seite als gelesen markiert.
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $notifications = $user->notifications()->latest()->limit(50)->get();
        $user->unreadNotifications()->update(['read_at' => now()]);

        return view('community.notifications', ['notifications' => $notifications]);
    }

    /** Zähler für Kopfzeile und Menüleiste (public/js/header-badges.js fragt alle 15 s ab). */
    public function counts(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'messages' => Inbox::unreadTotal($user),
            'notifications' => $user->unreadNotifications()->count(),
        ])->header('Cache-Control', 'no-store, private');
    }
}
