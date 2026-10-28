<?php

namespace App\Modules\Community\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Meldung einer Person oder Nachricht an die Moderation (Admin → Meldungen).
 */
class Report extends Model
{
    public const REASONS = [
        'spam' => 'Spam oder Werbung',
        'abuse' => 'Beleidigung oder Belästigung',
        'fake' => 'Falsches Profil',
        'inappropriate' => 'Unangemessene Inhalte',
        'other' => 'Sonstiges',
    ];

    protected $fillable = ['reporter_id', 'reportable_type', 'reportable_id', 'reason', 'note', 'status', 'resolved_at'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /** Gemeldete Person bzw. Nachricht (ohne Morph-Beziehung, damit gelöschte Ziele nicht stören). */
    public function target(): User|Message|null
    {
        return match ($this->reportable_type) {
            'user' => User::find($this->reportable_id),
            'message' => Message::with(['sender', 'conversation.course'])->find($this->reportable_id),
            default => null,
        };
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }
}
