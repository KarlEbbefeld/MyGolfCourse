<?php

namespace App\Modules\Community\Support;

use App\Models\User;
use App\Modules\Community\Models\Conversation;
use App\Modules\Community\Models\Message;
use Illuminate\Support\Collection;

/**
 * Posteingang: sichtbare Chats mit letzter Nachricht und Ungelesen-Zähler; Gesamtzahl für die Kopfzeile.
 */
class Inbox
{
    /** @var array<int, int> */
    private static array $unreadCache = [];

    /**
     * @return Collection<int, array{conversation: Conversation, last: ?Message, unread: int}>
     */
    public static function for(User $user): Collection
    {
        if ($user->club_id) {
            $user->loadMissing('club');
            if ($user->club?->isPublished()) {
                Conversation::club($user->club); // Club-Chat erscheint immer im Posteingang
            }
        }

        $blocked = $user->blockedIds();

        return Conversation::query()
            ->visibleTo($user)
            ->with(['course', 'userLow', 'userHigh'])
            ->get()
            ->map(function (Conversation $conversation) use ($user, $blocked) {
                $last = $conversation->messages()->reorder()->latest('id')
                    ->whereNotIn('user_id', $blocked)
                    ->with('sender')->first();

                return ['conversation' => $conversation, 'last' => $last, 'unread' => $conversation->unreadCountFor($user)];
            })
            // Direktchats ohne Nachrichten und mit blockierten Personen ausblenden
            ->filter(fn ($row) => $row['conversation']->isClub()
                || ($row['last'] && ! in_array($row['conversation']->partnerFor($user)?->id, $blocked, true)))
            ->sortByDesc(fn ($row) => [$row['conversation']->isClub() ? 1 : 0, $row['last']?->id ?? 0])
            ->values();
    }

    /** Ungelesene Nachrichten gesamt (Kopfzeile). */
    public static function unreadTotal(User $user): int
    {
        return self::$unreadCache[$user->id] ??= Conversation::query()
            ->visibleTo($user)
            ->get()
            ->sum(fn (Conversation $c) => $c->unreadCountFor($user));
    }
}
