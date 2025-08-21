<?php

namespace App\Observers;

use App\Models\ProductSale;

class ProductSaleObserver
{
    public function created(ProductSale $ps): void
    {
        if ($ps->quantity > 0) {
            $product = $ps->product ?? $ps->load('product')->product;
            if ($product) {
                $product->transactions()->create([
                    'store_id' => $ps->sale->store_id,
                    'type' => 'product_stock_out',
                    'amount' => $ps->unit_price * $ps->quantity,
                    'quantity' => $ps->quantity,
                    'note' => 'Stock out from sale',
                    'meta' => [
                        'sale_id' => $ps->sale_id,
                    ],
                ]);
                $product->decrement('stock', $ps->quantity);
            }
        }
    }

    public function updated(ProductSale $ps): void
    {
        // Only act if quantity changed and is > 0
        if ($ps->isDirty('quantity') && $ps->quantity > 0) {
            $product = $ps->product ?? $ps->load('product')->product;
            if ($product) {
                $product->transactions()->create([
                    'store_id' => $ps->sale->store_id,
                    'type' => 'product_stock_out',
                    'amount' => $ps->unit_price * $ps->quantity,
                    'quantity' => $ps->quantity,
                    'note' => 'Stock out from sale (direct update)',
                    'meta' => [
                        'sale_id' => $ps->sale_id,
                    ],
                ]);
                $product->decrement('stock', $ps->quantity);
            }
        }
    }
}
