<?php

namespace App\Observers;

use App\Enums\ProcurementStatus;
use App\Models\Procurement;

class ProcurementObserver
{
    public function updated(Procurement $procurement): void
    {
        // Only act when status changes to closed
        if ($procurement->isDirty('status') && $procurement->status === ProcurementStatus::Closed) {
            // Supplier debit transaction
            $procurement->transactions()
                ->create([
                    'store_id' => $procurement->store_id,
                    'type' => 'supplier_debit',
                    'amount' => $procurement->total_received_cost_price,
                    'note' => 'Procurement closed: supplier debit',
                ]);

            // For each received product, create stock-in transaction and update stock
            foreach ($procurement->procurementProducts as $pp) {
                if ($pp->received_quantity > 0) {
                    $product = $pp->product;

                    $product->price = $pp->received_unit_price;
                    $product->sale_price = $pp->received_unit_price * (1 + $pp->sale_percentage / 100);
                    $product->tax_percentage = $pp->received_tax_percentage;
                    $product->tax_amount = $pp->received_tax_amount;
                    $product->supplier_percentage = $pp->received_supplier_percentage;
                    $product->supplier_price = $pp->received_supplier_price;
                    $product->save();

                    $product->transactions()
                        ->create([
                            'store_id' => $procurement->store_id,
                            'type' => 'product_stock_in',
                            'amount' => $pp->received_unit_price * $pp->received_quantity,
                            'quantity' => $pp->received_quantity,
                            'note' => 'Stock in from procurement',
                            'meta' => [
                                'procurement_id' => $procurement->id,
                                'supplier_percentage' => $pp->supplier_percentage,
                                'cost_price' => $pp->received_unit_price,
                            ],
                        ]);
                    // Update product stock
                    $product->increment('stock', $pp->received_quantity);
                }
            }
        }
    }
}
