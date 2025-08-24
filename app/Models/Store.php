<?php

namespace App\Models;

use App\Enums\StoreStatus;
use Database\Factories\StoreFactory;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model implements HasCurrentTenantLabel
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => StoreStatus::class,
        ];
    }

    public function users(): BelongsToMany
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

    /** @return HasMany<\App\Models\Supplier, self> */
    public function suppliers(): HasMany
    {
        return $this->hasMany(\App\Models\Supplier::class);
    }

    /** @return HasMany<\App\Models\Procurement, self> */
    public function procurements(): HasMany
    {
        return $this->hasMany(\App\Models\Procurement::class);
    }

    /** @return HasMany<\App\Models\Transaction, self> */
    public function transactions(): HasMany
    {
        return $this->hasMany(\App\Models\Transaction::class);
    }

    /** @return HasMany<\App\Models\Brand, self> */
    public function brands(): HasMany
    {
        return $this->hasMany(\App\Models\Brand::class);
    }

    /** @return HasMany<\App\Models\Unit, self> */
    public function units(): HasMany
    {
        return $this->hasMany(\App\Models\Unit::class);
    }
}
