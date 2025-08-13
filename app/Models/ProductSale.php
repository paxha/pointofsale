<?php

namespace App\Models;

use App\Casts\PriceCast;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductSale extends Pivot
{
    protected function casts(): array
    {
        return [
            'unit_price' => PriceCast::class,
            'tax' => PriceCast::class,
            'price' => PriceCast::class,
        ];
    }
}
