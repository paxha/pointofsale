<?php

namespace App\Models;

use App\Casts\PriceCast;
use App\Enums\ProcurementStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Procurement extends Model
{
    /** @use HasFactory<\Database\Factories\ProcurementFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'reference',
        'status',
        'total_requested_quantity',
        'total_received_quantity',
        'total_requested_unit_price',
        'total_received_unit_price',
        'total_requested_tax_amount',
        'total_received_tax_amount',
        'total_requested_supplier_price',
        'total_received_supplier_price',
        'total_requested_supplier_percentage',
        'total_received_supplier_percentage',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProcurementStatus::class,
            'total_requested_unit_price' => PriceCast::class,
            'total_received_unit_price' => PriceCast::class,
            'total_requested_tax_amount' => PriceCast::class,
            'total_received_tax_amount' => PriceCast::class,
            'total_requested_supplier_price' => PriceCast::class,
            'total_received_supplier_price' => PriceCast::class,
            'total_requested_supplier_percentage' => 'float',
            'total_received_supplier_percentage' => 'float',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot(
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
            )
            ->withTimestamps();
    }

    public function procurementProducts(): HasMany
    {
        return $this->hasMany(ProcurementProduct::class);
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    public function recalculateTotals(): void
    {
        $this->loadMissing('procurementProducts');
        $products = $this->procurementProducts;

        $totalRequestedQuantity = (int) $products->sum('requested_quantity');
        $totalReceivedQuantity = (int) $products->sum('received_quantity');

        $totalRequestedUnitPrice = (int) $products->sum(fn ($p) => (int) ($p->requested_quantity ?? 0) * (int) ($p->requested_unit_price ?? 0));
        $totalRequestedSupplierPrice = (int) $products->sum(fn ($p) => (int) ($p->requested_quantity ?? 0) * (int) ($p->requested_supplier_price ?? 0));
        $totalRequestedTaxAmount = (int) $products->sum('requested_tax_amount');
        $totalRequestedSupplierPercentage = $totalRequestedQuantity > 0 ? $products->sum(fn ($p) => (float) ($p->requested_supplier_percentage ?? 0) * (int) ($p->requested_quantity ?? 0)) / $totalRequestedQuantity : 0;

        $totalReceivedUnitPrice = (int) $products->sum(fn ($p) => (int) ($p->received_quantity ?? 0) * (int) ($p->received_unit_price ?? 0));
        $totalReceivedSupplierPrice = (int) $products->sum(fn ($p) => (int) ($p->received_quantity ?? 0) * (int) ($p->received_supplier_price ?? 0));
        $totalReceivedTaxAmount = (int) $products->sum('received_tax_amount');
        $totalReceivedSupplierPercentage = $totalReceivedQuantity > 0 ? $products->sum(fn ($p) => (float) ($p->received_supplier_percentage ?? 0) * (int) ($p->received_quantity ?? 0)) / $totalReceivedQuantity : 0;

        $this->forceFill([
            'total_requested_quantity' => $totalRequestedQuantity,
            'total_received_quantity' => $totalReceivedQuantity,
            'total_requested_unit_price' => $totalRequestedUnitPrice,
            'total_requested_supplier_price' => $totalRequestedSupplierPrice,
            'total_requested_tax_amount' => $totalRequestedTaxAmount,
            'total_received_unit_price' => $totalReceivedUnitPrice,
            'total_received_supplier_price' => $totalReceivedSupplierPrice,
            'total_received_tax_amount' => $totalReceivedTaxAmount,
        ])->saveQuietly();
    }
}
