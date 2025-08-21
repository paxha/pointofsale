<?php

namespace App\Observers;

use App\Models\ProductSale;

class ProductSaleObserver
{
    public function created(ProductSale $ps): void
    {
        $product = $ps->product ?? $ps->load('product')->product;
        if ($product) {
            if ($ps->quantity > 0) {
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
            } elseif ($ps->quantity < 0) {
                $product->transactions()->create([
                    'store_id' => $ps->sale->store_id,
                    'type' => 'product_stock_in',
                    'amount' => $ps->unit_price * abs($ps->quantity),
                    'quantity' => abs($ps->quantity),
                    'note' => 'Stock in from sale return',
                    'meta' => [
                        'sale_id' => $ps->sale_id,
                    ],
                ]);
                $product->increment('stock', abs($ps->quantity));
            }
        }
    }

    public function updated(ProductSale $ps): void
    {
        if ($ps->isDirty('quantity')) {
            $product = $ps->product ?? $ps->load('product')->product;
            if ($product) {
                if ($ps->quantity > 0) {
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
                } elseif ($ps->quantity < 0) {
                    $product->transactions()->create([
                        'store_id' => $ps->sale->store_id,
                        'type' => 'product_stock_in',
                        'amount' => $ps->unit_price * abs($ps->quantity),
                        'quantity' => abs($ps->quantity),
                        'note' => 'Stock in from sale return (direct update)',
                        'meta' => [
                            'sale_id' => $ps->sale_id,
                        ],
                    ]);
                    $product->increment('stock', abs($ps->quantity));
                }
            }
        }
    }
}
