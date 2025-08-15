<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Procurement extends Model
{
    /** @use HasFactory<\Database\Factories\ProcurementFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'total_requested_quantity',
        'total_received_quantity',
        'total_requested_unit_price',
        'total_received_unit_price',
        'total_requested_tax_amount',
        'total_received_tax_amount',
        'total_requested_cost_price',
        'total_received_cost_price',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot(
                'name',
                'total_requested_quantity',
                'total_received_quantity',
                'total_requested_unit_price',
                'total_received_unit_price',
                'total_requested_tax_amount',
                'total_received_tax_amount',
                'total_requested_cost_price',
                'total_received_cost_price'
            )
            ->withTimestamps();
    }
}
