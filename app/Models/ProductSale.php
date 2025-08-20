<?php

namespace App\Models;

use App\Casts\PriceCast;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductSale extends Pivot
{
    protected function casts(): array
    {
        return [
            'unit_price' => PriceCast::class,
            'tax' => PriceCast::class,
            'price' => PriceCast::class,
            'supplier_percentage_at_sale' => 'float',
            'supplier_cost_price_at_sale' => PriceCast::class,
            'customer_price' => PriceCast::class,
            'customer_discount' => PriceCast::class,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
