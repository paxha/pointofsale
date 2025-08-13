<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    public static function boot(): void
    {
        parent::boot();

        self::creating(function (Role $role) {
            $teamId = filament()->getTenant()->getKey();

            $role->team_id = $teamId;
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'team_id');
    }
}
