<?php

namespace App\Observers;

use App\Enums\SaleStatus;
use App\Models\Sale;

class SaleObserver
{
    public function created(Sale $sale): void
    {
        // Create transaction for the customer if exists
        if ($sale->customer) {
            $transaction = $sale->customer->transactions()->make();
            $transaction->forceFill([
                'store_id' => $sale->store_id,
                'type' => 'customer_credit',
                'amount' => $sale->total,
                'note' => 'Sale: customer credit',
            ])->save();
        }
        // Do NOT update stock here; products are not yet attached
        // Create transaction for the sale itself so it appears in sale detail page
        $transaction = $sale->transactions()->make();
        $transaction->forceFill([
            'store_id' => $sale->store_id,
            'type' => 'sale_record',
            'amount' => $sale->total,
            'note' => 'Sale record transaction',
        ])->save();
    }

    public function updated(Sale $sale): void
    {
        $originalStatus = $sale->getOriginal('status');
        $newStatus = $sale->status;
        $statusJustCompleted = $newStatus === SaleStatus::Completed && $originalStatus !== SaleStatus::Completed;

        // Only act if status just became Completed
        if ($statusJustCompleted) {
            $this->handleStockOnCompleted($sale);
        }
    }

    private function handleStockOnCompleted(Sale $sale): void
    {
        $sale->load('products');
        foreach ($sale->products as $product) {
            $pivot = $product->pivot;
            // Only update stock if no transaction exists for this sale/product
            $transactionExists = $product->transactions()->where('sale_id', $sale->id)->exists();
            if ($transactionExists) {
                continue;
            }
            if ($pivot->quantity > 0) {
                $product->transactions()->create([
                    'store_id' => $sale->store_id,
                    'type' => 'product_stock_out',
                    'amount' => $pivot->unit_price * $pivot->quantity,
                    'quantity' => $pivot->quantity,
                    'note' => 'Stock out from sale',
                    'meta' => [
                        'sale_id' => $sale->id,
                        'supplier_percentage' => $product->supplier_percentage,
                        'supplier_cost_price' => $product->cost_price,
                        'customer_price' => $pivot->unit_price,
                        'customer_discount' => $pivot->discount,
                    ],
                ]);
                $product->decrement('stock', $pivot->quantity);
            } elseif ($pivot->quantity < 0) {
                $product->transactions()->create([
                    'store_id' => $sale->store_id,
                    'type' => 'product_stock_in',
                    'amount' => $pivot->unit_price * abs($pivot->quantity),
                    'quantity' => abs($pivot->quantity),
                    'note' => 'Stock in from sale return',
                    'meta' => [
                        'sale_id' => $sale->id,
                        'supplier_percentage' => $product->supplier_percentage,
                        'supplier_cost_price' => $product->cost_price,
                        'customer_price' => $pivot->unit_price,
                        'customer_discount' => $pivot->discount,
                    ],
                ]);
                $product->increment('stock', abs($pivot->quantity));
            }
        }
    }
}
