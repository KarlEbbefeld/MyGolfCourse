<?php

namespace App\Core\Enums;

/**
 * Basisrollen (docs/ARCHITECTURE.md § 7). Gäste haben keine Rolle.
 */
enum Role: string
{
    case User = 'user';
    case Editor = 'editor';
    case Admin = 'admin';

    public function label(): string
    {
        return __('app.roles.'.$this->value);
    }

    /** Darf Plätze, Bahnen, Medien und Hindernisse pflegen. */
    public function canEditContent(): bool
    {
        return $this === self::Editor || $this === self::Admin;
    }

    /** Darf Plätze veröffentlichen oder deaktivieren. */
    public function canPublish(): bool
    {
        return $this === self::Admin;
    }
}
