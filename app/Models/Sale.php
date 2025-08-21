<?php

namespace App\Models;

use App\Casts\PriceCast;
use App\Enums\SalePaymentStatus;
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
        'tax',
        'discount',
        'total',
        'status',
        'payment_status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => PriceCast::class,
            'tax' => PriceCast::class,
            'discount' => 'float',
            'total' => PriceCast::class,
            'status' => SaleStatus::class,
            'payment_status' => SalePaymentStatus::class,
            'paid_at' => 'datetime',
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
            ->withPivot('quantity', 'unit_price', 'tax', 'discount', 'supplier_price');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }
}
