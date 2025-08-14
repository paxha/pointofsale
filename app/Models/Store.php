<?php

namespace App\Models;

use App\Enums\StoreStatus;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Database\Factories\StoreFactory;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model implements HasCurrentTenantLabel
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
    ];

    protected function casts(): array
    {
        return [
            'status' => StoreStatus::class,
        ];
    }

    function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function getCurrentTenantLabel(): string
    {
        return 'Active Store';
    }

    /** @return HasMany<\App\Models\Category, self> */
    public function categories(): HasMany
    {
        return $this->hasMany(\App\Models\Category::class);
    }


    /** @return HasMany<\App\Models\Product, self> */
    public function products(): HasMany
    {
        return $this->hasMany(\App\Models\Product::class);
    }


    /** @return HasMany<\App\Models\Sale, self> */
    public function sales(): HasMany
    {
        return $this->hasMany(\App\Models\Sale::class);
    }


    /** @return HasMany<\App\Models\Customer, self> */
    public function customers(): HasMany
    {
        return $this->hasMany(\App\Models\Customer::class);
    }

}
