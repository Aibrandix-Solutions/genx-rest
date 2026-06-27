<?php

namespace App\Listeners;

use App\Enums\ActivityEvent;
use App\Support\ActivityLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class LogAuthenticationActivity
{
    public function handleLogin(Login $event): void
    {
        $user = $event->user;

        if (!$user) {
            return;
        }

        ActivityLogger::recordEvent(
            activityEvent: ActivityEvent::UserLoggedIn,
            description: "User {$user->name} logged in",
            subject: $user,
            properties: [
                'user_id' => $user->id,
                'email' => $user->email,
                'guard' => $event->guard,
            ],
            restaurantId: $user->restaurant_id ? (int) $user->restaurant_id : null,
            branchId: $user->branch_id ? (int) $user->branch_id : null,
            causerId: (int) $user->id,
            causerName: $user->name,
        );
    }

    public function handleLogout(Logout $event): void
    {
        $user = $event->user;

        if (!$user) {
            return;
        }

        ActivityLogger::recordEvent(
            activityEvent: ActivityEvent::UserLoggedOut,
            description: "User {$user->name} logged out",
            subject: $user,
            properties: [
                'user_id' => $user->id,
                'email' => $user->email,
                'guard' => $event->guard,
            ],
            restaurantId: $user->restaurant_id ? (int) $user->restaurant_id : null,
            branchId: $user->branch_id ? (int) $user->branch_id : null,
            causerId: (int) $user->id,
            causerName: $user->name,
        );
    }
}
