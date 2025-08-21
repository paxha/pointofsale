<?php

namespace App\Observers;

use App\Models\Sale;

class SaleObserver
{
    public function created(Sale $sale): void
    {
        // Create transaction for the customer if exists
        if ($sale->customer) {
            $sale->customer->transactions()
                ->create([
                    'store_id' => $sale->store_id,
                    'type' => 'customer_credit',
                    'amount' => $sale->total,
                    'note' => 'Sale: customer credit',
                ]);
        }

        // For each sold product, create stock-out transaction and update stock
        foreach ($sale->products as $product) {
            $pivot = $product->pivot;
            if ($pivot->quantity > 0) {
                // Save supplier/customer details in pivot
                $product->sales()->updateExistingPivot($sale->id, [
                    'supplier_percentage_at_sale' => $product->supplier_percentage,
                    'supplier_cost_price_at_sale' => $product->cost_price,
                    'customer_price' => $pivot->unit_price,
                    'customer_discount' => $pivot->discount,
                ]);
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
                    'supplier_percentage' => $product->supplier_percentage,
                    'supplier_cost_price' => $product->cost_price,
                    'customer_price' => $pivot->unit_price,
                    'customer_discount' => $pivot->discount,
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                ]);
                // Update product stock
                $product->decrement('stock', $pivot->quantity);
            }
        }

        // Create transaction for the sale itself so it appears in sale detail page
        $sale->transactions()->create([
            'store_id' => $sale->store_id,
            'type' => 'sale_record',
            'amount' => $sale->total,
            'note' => 'Sale record transaction',
        ]);
    }
}
