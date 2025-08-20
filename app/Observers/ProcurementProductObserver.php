<?php

namespace App\Observers;

use App\Models\ProcurementProduct;
use App\Models\Transaction;
use App\Models\Product;

class ProcurementProductObserver
{
    public function updated(ProcurementProduct $pp): void
    {
        // Only act if received_quantity changed and is > 0
        if ($pp->isDirty('received_quantity') && $pp->received_quantity > 0) {
            $pp->product->transactions()->create([
                'store_id' => $pp->procurement->store_id,
                'type' => 'product_stock_in',
                'amount' => $pp->received_unit_price * $pp->received_quantity,
                'quantity' => $pp->received_quantity,
                'note' => 'Stock in from procurement (direct update)',
                'meta' => [
                    'procurement_id' => $pp->procurement_id,
                ],
            ]);
            // Update product stock
            $pp->product->increment('stock', $pp->received_quantity);
        }
    }
}
