<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{

    public function creating(User $user)
    {
        if (branch()) {
            $user->restaurant_id = restaurant()->id;
            $user->branch_id = branch()->id;
        }
        $user->locale = global_setting()->locale;
    }

    public function created(User $user): void
    {
        User::clearPosBootstrapCacheFor($user);
    }

    public function updated(User $user): void
    {
        User::clearPosBootstrapCacheFor($user);
    }

    public function deleted(User $user): void
    {
        User::clearPosBootstrapCacheFor($user);
    }

}
