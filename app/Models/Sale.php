<?php

namespace App\Models;

use App\Casts\PriceCast;
use App\Enums\SaleStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'subtotal',
        'discount',
        'tax',
        'total',
        'payment_method',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => PriceCast::class,
            'tax' => PriceCast::class,
            'total' => PriceCast::class,
            'status' => SaleStatus::class,
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->using(ProductSale::class)
            ->withPivot('unit_price', 'quantity', 'tax', 'price', 'discount');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
