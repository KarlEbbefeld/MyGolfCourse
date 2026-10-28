<?php

namespace App\Modules\Community\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Mitteilung in der Glocke: jemand folgt dir (bzw. ihr seid jetzt Freunde).
 */
class NewFollower extends Notification
{
    public function __construct(public readonly User $follower, public readonly bool $nowFriends) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'follower',
            'user_id' => $this->follower->id,
            'username' => $this->follower->username,
            'name' => $this->follower->name,
            'text' => $this->nowFriends
                ? $this->follower->name.' folgt dir auch – ihr seid jetzt Freunde.'
                : $this->follower->name.' folgt dir jetzt.',
        ];
    }
}
