<?php

namespace App\Services;

use App\Enums\ProcurementStatus;
use App\Models\Procurement;

class ProcurementTransactionService
{
    public function handleProcurementClosed(Procurement $procurement): void
    {
        // Only act if procurement is closed
        if ($procurement->status !== ProcurementStatus::Closed) {
            return;
        }

        // Supplier debit transaction (for supplier, with reference)
        if ($procurement->supplier) {
            $supplier = $procurement->supplier;
            $lastTransaction = $supplier->transactions()->latest('created_at')->first();
            $previousBalance = $lastTransaction?->balance ?? 0;
            $amount = -abs($procurement->total_received_supplier_price); // supplier_debit is a minus entry
            $newBalance = $previousBalance + $amount; // supplier_debit decreases balance

            $supplier->transactions()
                ->create([
                    'store_id' => $procurement->store_id,
                    'type' => 'supplier_debit',
                    'amount' => $amount,
                    'note' => 'Procurement closed: supplier debit',
                    'referenceable_type' => Procurement::class,
                    'referenceable_id' => $procurement->id,
                    'meta' => [
                        'procurement_id' => $procurement->id,
                        'supplier_id' => $procurement->supplier_id,
                    ],
                    'balance' => $newBalance,
                ]);
        }

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

                $lastProductTransaction = $product->transactions()->latest('created_at')->first();
                $previousProductBalance = $lastProductTransaction?->balance ?? 0;
                $newProductBalance = $previousProductBalance + $pp->received_quantity; // stock in increases balance

                $product->transactions()->create([
                    'store_id' => $procurement->store_id,
                    'type' => 'product_stock_in',
                    'amount' => $pp->received_quantity,
                    'note' => 'Stock in from procurement',
                    'referenceable_type' => Procurement::class,
                    'referenceable_id' => $procurement->id,
                    'meta' => [
                        'procurement_id' => $procurement->id,
                        'supplier_percentage' => $pp->supplier_percentage,
                        'cost_price' => $pp->received_unit_price,
                    ],
                    'balance' => $newProductBalance,
                ]);
                // Update product stock
                $product->increment('stock', $pp->received_quantity);
            }
        }
    }
}
