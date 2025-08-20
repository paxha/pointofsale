<?php

namespace App\Models;

use App\Casts\PriceCast;
use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'sku',
        'barcode',
        'price',
        'sale_price',
        'tax_percentage',
        'stock',
        'status',
        'supplier_percentage',
        'tax_amount',
        'supplier_price',
        'sale_percentage',
    ];

    protected function casts(): array
    {
        return [
            'price' => PriceCast::class,
            'sale_price' => PriceCast::class,
            'tax_amount' => PriceCast::class,
            'supplier_price' => PriceCast::class,
            'status' => ProductStatus::class,
            'sale_percentage' => 'float',
            'supplier_percentage' => 'float',
            'tax_percentage' => 'float',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }
}
