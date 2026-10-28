<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Community\Models\Message;
use App\Modules\Community\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Moderation: Meldungen zu Personen und Nachrichten prüfen; Nachrichten ausblenden.
 */
class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status') === 'erledigt' ? ['resolved', 'dismissed'] : ['open'];

        return view('admin.reports.index', [
            'reports' => Report::query()->whereIn('status', $status)->with('reporter')->latest()->limit(100)->get(),
            'showDone' => $status !== ['open'],
        ]);
    }

    public function update(Request $request, Report $report): RedirectResponse
    {
        $action = $request->validate(['action' => ['required', 'in:hide,remove_avatar,dismiss,resolve']])['action'];

        if ($action === 'hide' && $report->reportable_type === 'message') {
            Message::query()->whereKey($report->reportable_id)->update(['hidden_at' => now()]);
        }

        if ($action === 'remove_avatar' && $report->reportable_type === 'user') {
            User::find($report->reportable_id)?->deleteAvatar();
        }

        $report->update([
            'status' => $action === 'dismiss' ? 'dismissed' : 'resolved',
            'resolved_at' => now(),
        ]);

        return back()->with('status', match ($action) {
            'hide' => 'Nachricht ausgeblendet, Meldung erledigt.',
            'remove_avatar' => 'Profilbild entfernt, Meldung erledigt.',
            'dismiss' => 'Meldung verworfen.',
            default => 'Meldung erledigt.',
        });
    }
}
