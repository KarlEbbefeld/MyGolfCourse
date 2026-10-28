<?php

namespace App\Modules\Community\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Courses\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Club-Seite eines Platzes: Mitglieder, Beitreten/Austreten, Zugang zum Club-Chat.
 * Mitgliedschaft ist eine Selbstauskunft (keine Prüfung durch den Club) – eine Person gehört zu höchstens einem Club.
 */
class ClubController extends Controller
{
    public function show(Request $request, Course $course): View
    {
        abort_unless($course->isPublished(), 404);
        $me = $request->user();

        $members = $me
            ? CommunityController::discoverable($me)->where('club_id', $course->id)->orderBy('name')->get()
            : User::query()->where('club_id', $course->id)->where('profile_public', true)->whereNotNull('username')->orderBy('name')->get();

        return view('community.club', [
            'course' => $course,
            'me' => $me,
            'isMember' => $me?->club_id === $course->id,
            'members' => $members,
            'memberCount' => User::query()->where('club_id', $course->id)->count(),
        ]);
    }

    public function toggle(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->isPublished(), 404);
        $me = $request->user();

        if ($me->club_id === $course->id) {
            $me->forceFill(['club_id' => null])->save();

            return back()->with('status', 'Du bist nicht mehr Mitglied bei '.$course->name.'.');
        }

        $previous = $me->club_id ? Course::find($me->club_id)?->name : null;
        $me->forceFill(['club_id' => $course->id])->save();

        return back()->with('status', 'Willkommen im Club '.$course->name.'!'.($previous ? ' (Mitgliedschaft bei '.$previous.' beendet.)' : '').' Der Club-Chat steht jetzt in deinen Nachrichten.');
    }
}
