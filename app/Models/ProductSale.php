<?php

namespace App\Models;

use App\Casts\PriceCast;
use App\Observers\ProductSaleObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[ObservedBy(ProductSaleObserver::class)]
class ProductSale extends Pivot
{
    protected function casts(): array
    {
        return [
            'unit_price' => PriceCast::class,
            'tax' => PriceCast::class,
            'discount' => 'float',
            'supplier_price' => PriceCast::class,
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
