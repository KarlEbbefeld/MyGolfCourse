<?php

namespace App\Modules\Community\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    public const MAX_LENGTH = 2000;

    protected $fillable = [];

    protected function casts(): array
    {
        return ['hidden_at' => 'datetime'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isHidden(): bool
    {
        return $this->hidden_at !== null;
    }

    /** Daten für den Chat im Browser (Text wird dort per textContent eingefügt, nie als HTML). */
    public function toChatArray(User $viewer): array
    {
        return [
            'id' => $this->id,
            'mine' => $this->user_id === $viewer->id,
            'sender' => $this->sender?->name ?? 'Gelöschtes Konto',
            'senderUrl' => $this->sender?->username ? route('players.show', $this->sender->username) : null,
            'initial' => mb_strtoupper(mb_substr($this->sender?->name ?? '?', 0, 1)),
            'body' => $this->isHidden() ? 'Diese Nachricht wurde von der Moderation ausgeblendet.' : $this->body,
            'hidden' => $this->isHidden(),
            'time' => $this->created_at?->timezone(config('app.display_timezone', 'Europe/Berlin'))->format('d.m.Y H:i'),
            'day' => $this->created_at?->timezone(config('app.display_timezone', 'Europe/Berlin'))->toDateString(),
        ];
    }
}
