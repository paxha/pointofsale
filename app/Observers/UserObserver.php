<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $teamId = filament()->getTenant()->getKey();

        $user->stores()->attach($teamId);
    }
}
