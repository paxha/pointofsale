<?php

namespace App\Models;

use App\Observers\RoleObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role as SpatieRole;

#[ObservedBy(RoleObserver::class)]
class Role extends SpatieRole
{
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'team_id');
    }
}
