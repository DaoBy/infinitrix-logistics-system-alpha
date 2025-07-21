<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public static function send(User $user, string $title, string $message, string $type = 'info'): void
    {
        Notification::create([
            'user_id' => $user->id,
            'title'   => $title,
            'message' => $message,
            'type'    => $type,
        ]);
    }
}
