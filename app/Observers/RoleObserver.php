<?php

namespace App\Observers;

use App\Models\Role;

class RoleObserver
{
    public function creating(Role $role): void
    {
        $teamId = filament()->getTenant()->getKey();

        $role->team_id = $teamId;
    }
}
