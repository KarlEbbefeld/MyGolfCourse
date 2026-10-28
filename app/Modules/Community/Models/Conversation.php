<?php

namespace App\Modules\Community\Models;

use App\Models\User;
use App\Modules\Courses\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Chat: entweder direkt zwischen zwei Personen (type direct) oder der Club-Chat eines Platzes (type club).
 * Teilnehmer werden nur serverseitig gesetzt.
 */
class Conversation extends Model
{
    public const DIRECT = 'direct';

    public const CLUB = 'club';

    protected $fillable = [];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    /** Findet oder startet den Direktchat zweier Personen (pro Paar genau einer). */
    public static function direct(User $a, User $b): self
    {
        [$low, $high] = $a->id < $b->id ? [$a->id, $b->id] : [$b->id, $a->id];
        $find = fn () => self::query()->where('type', self::DIRECT)->where('user_low_id', $low)->where('user_high_id', $high)->first();

        if ($existing = $find()) {
            return $existing;
        }

        try {
            return tap((new self)->forceFill(['type' => self::DIRECT, 'user_low_id' => $low, 'user_high_id' => $high]))->save();
        } catch (UniqueConstraintViolationException) {
            return $find();
        }
    }

    /** Club-Chat eines Platzes (wird beim ersten Öffnen angelegt). */
    public static function club(Course $course): self
    {
        $find = fn () => self::query()->where('type', self::CLUB)->where('course_id', $course->id)->first();

        if ($existing = $find()) {
            return $existing;
        }

        try {
            return tap((new self)->forceFill(['type' => self::CLUB, 'course_id' => $course->id]))->save();
        } catch (UniqueConstraintViolationException) {
            return $find();
        }
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function userLow(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_low_id');
    }

    public function userHigh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_high_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('id');
    }

    public function isClub(): bool
    {
        return $this->type === self::CLUB;
    }

    /** Darf $user den Chat sehen? Direkt: Teilnehmer. Club: Mitglied des Clubs. */
    public function canView(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->isClub()
            ? $user->club_id !== null && $user->club_id === $this->course_id
            : in_array($user->id, [$this->user_low_id, $this->user_high_id], true);
    }

    /** Gesprächspartner im Direktchat (null bei gelöschtem Konto oder Club-Chat). */
    public function partnerFor(User $user): ?User
    {
        if ($this->isClub()) {
            return null;
        }

        return $user->id === $this->user_low_id ? $this->userHigh : $this->userLow;
    }

    public function title(User $viewer): string
    {
        return $this->isClub()
            ? 'Club-Chat · '.($this->course?->name ?? 'Club')
            : ($this->partnerFor($viewer)?->name ?? 'Gelöschtes Konto');
    }

    /** Chats, die $user sehen darf: eigene Direktchats + Club-Chat des eigenen Clubs. */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->where(function (Builder $q) use ($user) {
            $q->where(fn (Builder $d) => $d->where('type', self::DIRECT)
                ->where(fn (Builder $p) => $p->where('user_low_id', $user->id)->orWhere('user_high_id', $user->id)));
            if ($user->club_id) {
                $q->orWhere(fn (Builder $c) => $c->where('type', self::CLUB)->where('course_id', $user->club_id));
            }
        });
    }

    public function lastReadBy(User $user): int
    {
        return (int) DB::table('conversation_reads')->where('conversation_id', $this->id)->where('user_id', $user->id)->value('last_read_message_id');
    }

    public function markReadUpTo(User $user, int $messageId): void
    {
        if ($messageId <= 0) {
            return;
        }

        // Lesestand ist Nebensache: Fehler (z. B. kurz gesperrte Datenbank) dürfen den Chat nicht abbrechen
        rescue(fn () => DB::table('conversation_reads')->upsert(
            [['conversation_id' => $this->id, 'user_id' => $user->id, 'last_read_message_id' => $messageId]],
            ['conversation_id', 'user_id'],
            ['last_read_message_id' => DB::raw('CASE WHEN last_read_message_id > '.(int) $messageId.' THEN last_read_message_id ELSE '.(int) $messageId.' END')],
        ));
    }

    /** Ungelesene Nachrichten anderer (ohne ausgeblendete und ohne Nachrichten blockierter Personen). */
    public function unreadCountFor(User $user): int
    {
        return $this->messages()
            ->reorder()
            ->where('id', '>', $this->lastReadBy($user))
            ->whereNull('hidden_at')
            ->where(fn (Builder $q) => $q->where('user_id', '!=', $user->id)->orWhereNull('user_id'))
            ->whereNotIn('user_id', $user->blockedIds())
            ->count();
    }
}
