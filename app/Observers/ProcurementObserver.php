<?php

namespace App\Observers;

use App\Models\Procurement;
use App\Models\Transaction;
use App\Models\Product;

class ProcurementObserver
{
    public function updated(Procurement $procurement): void
    {
        // Only act when status changes to closed
        if ($procurement->isDirty('status') && $procurement->status->value === 'closed') {
            // Supplier debit transaction
            $procurement->transactions()->create([
                'store_id' => $procurement->store_id,
                'type' => 'supplier_debit',
                'amount' => $procurement->total_received_cost_price,
                'note' => 'Procurement closed: supplier debit',
            ]);

            // For each received product, create stock-in transaction and update stock
            foreach ($procurement->procurementProducts as $pp) {
                if ($pp->received_quantity > 0) {
                    $pp->product->transactions()->create([
                        'store_id' => $procurement->store_id,
                        'type' => 'product_stock_in',
                        'amount' => $pp->received_unit_price * $pp->received_quantity,
                        'quantity' => $pp->received_quantity,
                        'note' => 'Stock in from procurement',
                        'meta' => [
                            'procurement_id' => $procurement->id,
                        ],
                    ]);
                    // Update product stock
                    $pp->product->increment('stock', $pp->received_quantity);
                }
            }
        }
    }
}
