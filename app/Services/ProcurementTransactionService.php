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
            $lastBalance = $supplier->transactions()->latest('id')->value('amount_balance') ?? 0;
            $amount = -abs($procurement->total_received_supplier_price); // supplier_debit is a minus entry
            $newBalance = $lastBalance + $amount; // supplier_debit decreases balance

            $supplier->transactions()
                ->create([
                    'store_id' => $procurement->store_id,
                    'referenceable_type' => Procurement::class,
                    'referenceable_id' => $procurement->id,
                    'type' => 'supplier_credit',
                    'amount' => $amount,
                    'note' => 'Procurement closed: supplier credit',
                    'amount_balance' => $newBalance,
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

                $lastProductBalance = $product->transactions()->latest('id')->value('quantity_balance') ?? 0;
                $newProductBalance = $lastProductBalance + $pp->received_quantity; // stock in increases balance

                $product->transactions()->create([
                    'store_id' => $procurement->store_id,
                    'type' => 'product_stock_in',
                    'quantity' => $pp->received_quantity,
                    'note' => 'Stock in from procurement',
                    'referenceable_type' => Procurement::class,
                    'referenceable_id' => $procurement->id,
                    'meta' => [
                        'procurement_id' => $procurement->id,
                        'supplier_percentage' => $pp->supplier_percentage,
                        'cost_price' => $pp->received_unit_price,
                    ],
                    'quantity_balance' => $newProductBalance,
                ]);

                // Update product stock
                $product->stock = $newProductBalance;
                $product->save();
            }
        }
    }
}
