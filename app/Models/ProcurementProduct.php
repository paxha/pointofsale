<?php

namespace App\Models;

use App\Casts\PriceCast;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProcurementProduct extends Pivot
{
    protected $table = 'procurement_product';

    public $incrementing = true;

    protected $fillable = [
        'procurement_id',
        'product_id',
        'requested_quantity',
        'requested_unit_price',
        'requested_tax_percentage',
        'requested_tax_amount',
        'requested_supplier_percentage',
        'requested_supplier_price',
        'received_quantity',
        'received_unit_price',
        'received_tax_percentage',
        'received_tax_amount',
        'received_supplier_percentage',
        'received_supplier_price',
    ];

    protected function casts(): array
    {
        return [
            'requested_unit_price' => PriceCast::class,
            'requested_cost_price' => PriceCast::class,
            'received_unit_price' => PriceCast::class,
            'received_cost_price' => PriceCast::class,
            'supplier_percentage' => 'float',
        ];
    }

    public function procurement(): BelongsTo
    {
        return $this->belongsTo(Procurement::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
