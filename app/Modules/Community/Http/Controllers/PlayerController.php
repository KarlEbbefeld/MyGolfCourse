<?php

namespace App\Modules\Community\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Community\Models\Conversation;
use App\Modules\Community\Models\Report;
use App\Modules\Community\Notifications\NewFollower;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Spielerprofil und Aktionen gegenüber einer Person: folgen, Nachricht, blockieren, melden.
 */
class PlayerController extends Controller
{
    public function show(Request $request, User $user): View
    {
        $viewer = $request->user();
        // Wer mich blockiert hat, ist für mich nicht auffindbar (umgekehrt sehe ich das Profil, um die Blockierung aufzuheben)
        abort_if($viewer && $user->hasBlocked($viewer), 404);

        $visible = $user->isVisibleTo($viewer);
        $rounds = $visible ? $user->rounds()->with('course')->get() : collect();

        return view('community.player', [
            'player' => $user,
            'viewer' => $viewer,
            'visible' => $visible,
            'isMe' => $viewer?->is($user) ?? false,
            'isFollowing' => $viewer ? $viewer->isFollowing($user) : false,
            'followsMe' => $viewer ? $user->isFollowing($viewer) : false,
            'iBlocked' => $viewer ? $viewer->hasBlocked($user) : false,
            'canMessage' => $viewer ? $user->canBeMessagedBy($viewer) : false,
            'club' => $visible && $user->club_id ? $user->club()->published()->first() : null,
            'stats' => [
                'followers' => $user->followers()->count(),
                'following' => $user->following()->count(),
                'rounds' => $rounds->count(),
                'courses' => $rounds->pluck('course_id')->unique()->count(),
            ],
            'playedCourses' => $rounds->pluck('course')->filter(fn ($c) => $c?->isPublished())->unique('id')->take(8),
            'friends' => $visible ? $user->friends()->orderBy('name')->limit(12)->get() : collect(),
            'reasons' => Report::REASONS,
        ]);
    }

    public function follow(Request $request, User $user): RedirectResponse
    {
        $me = $request->user();
        abort_if($me->is($user) || $me->isBlockedWith($user), 403);

        if ($me->isFollowing($user)) {
            $me->following()->detach($user->id);

            return back()->with('status', 'Du folgst '.$user->name.' nicht mehr.');
        }

        $me->following()->attach($user->id);
        $nowFriends = $user->isFollowing($me);
        $user->notify(new NewFollower($me, $nowFriends));

        return back()->with('status', $nowFriends ? 'Ihr seid jetzt Freunde.' : 'Du folgst jetzt '.$user->name.'.');
    }

    public function block(Request $request, User $user): RedirectResponse
    {
        $me = $request->user();
        abort_if($me->is($user), 403);

        if ($me->hasBlocked($user)) {
            DB::table('blocks')->where('blocker_id', $me->id)->where('blocked_id', $user->id)->delete();

            return back()->with('status', $user->name.' ist nicht mehr blockiert.');
        }

        DB::transaction(function () use ($me, $user) {
            DB::table('blocks')->insertOrIgnore(['blocker_id' => $me->id, 'blocked_id' => $user->id, 'created_at' => now(), 'updated_at' => now()]);
            // Blockieren beendet Folgen in beide Richtungen
            DB::table('follows')->where(fn ($q) => $q->where('follower_id', $me->id)->where('followed_id', $user->id))
                ->orWhere(fn ($q) => $q->where('follower_id', $user->id)->where('followed_id', $me->id))
                ->delete();
        });

        return redirect()->route('community.index')->with('status', $user->name.' ist blockiert. Ihr seht eure Profile und Nachrichten nicht mehr.');
    }

    public function message(Request $request, User $user): RedirectResponse
    {
        $me = $request->user();

        if (! $me->hasVerifiedEmail()) {
            return back()->with('status', 'Bitte bestätige zuerst deine E-Mail-Adresse, um Nachrichten zu schreiben.');
        }
        abort_unless($user->canBeMessagedBy($me), 403);

        return redirect()->route('chats.show', Conversation::direct($me, $user));
    }

    public function report(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys(Report::REASONS))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        Report::create($data + ['reporter_id' => $request->user()->id, 'reportable_type' => 'user', 'reportable_id' => $user->id]);

        return back()->with('status', 'Danke, deine Meldung ist eingegangen. Wir schauen sie uns an.');
    }
}
