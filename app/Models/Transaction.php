<?php

namespace App\Models;

use App\Casts\PriceCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'transactionable_type',
        'transactionable_id',
        'type',
        'amount',
        'quantity',
        'note',
        'meta',
        'supplier_percentage',
        'supplier_cost_price',
        'customer_percentage',
        'customer_price',
        'sale_id',
        'product_id',
        'procurement_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => PriceCast::class,
            'meta' => 'array',
            'supplier_percentage' => 'float',
            'supplier_cost_price' => PriceCast::class,
            'customer_percentage' => 'float',
            'customer_price' => PriceCast::class,
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function referenceable(): MorphTo
    {
        return $this->morphTo('referenceable');
    }
}
