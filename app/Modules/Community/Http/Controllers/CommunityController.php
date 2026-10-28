<?php

namespace App\Modules\Community\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Community-Übersicht: eigener Club, Freunde, Follower, Spielersuche.
 * Gefunden werden nur öffentliche Profile und Freunde; Blockierte nie.
 */
class CommunityController extends Controller
{
    public function index(Request $request): View
    {
        $me = $request->user();
        $search = trim((string) ($request->validate(['q' => ['nullable', 'string', 'max:60']])['q'] ?? ''));
        $me->loadMissing('club');

        $results = $search !== '' ? $this->discoverable($me)
            ->where(function (Builder $q) use ($search) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
                $q->where('name', 'like', $like)->orWhere('username', 'like', $like);
            })
            ->with('club')->orderBy('name')->limit(30)->get() : collect();

        return view('community.index', [
            'me' => $me,
            'search' => $search,
            'results' => $results,
            'friends' => $me->friends()->with('club')->orderBy('name')->get(),
            'followers' => $me->followers()->whereNotIn('users.id', $me->blockedIds())->with('club')->latest('follows.created_at')->limit(12)->get(),
            'following' => $me->following()->whereNotIn('users.id', $me->blockedIds())->with('club')->orderBy('name')->limit(24)->get(),
            'clubMembers' => $me->club_id
                ? $this->discoverable($me)->where('club_id', $me->club_id)->with('club')->orderBy('name')->limit(12)->get()
                : collect(),
            'clubMemberCount' => $me->club_id ? User::query()->where('club_id', $me->club_id)->count() : 0,
        ]);
    }

    /** Personen, die $me finden darf: öffentlich oder befreundet, nicht ich selbst, nicht blockiert. */
    public static function discoverable(User $me): Builder
    {
        $friendIds = DB::table('follows as a')
            ->join('follows as b', fn ($j) => $j->on('a.followed_id', '=', 'b.follower_id')->on('a.follower_id', '=', 'b.followed_id'))
            ->where('a.follower_id', $me->id)
            ->pluck('a.followed_id');

        return User::query()
            ->whereNotNull('username')
            ->where('users.id', '!=', $me->id)
            ->whereNotIn('users.id', $me->blockedIds())
            ->where(fn (Builder $q) => $q->where('profile_public', true)->orWhereIn('users.id', $friendIds));
    }
}
