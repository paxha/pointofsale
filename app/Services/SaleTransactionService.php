<?php

namespace App\Services;

use App\Enums\SalePaymentStatus;
use App\Enums\SaleStatus;
use App\Models\Sale;

class SaleTransactionService
{
    public function handleSaleOnCompleted(Sale $sale): void
    {
        if ($sale->status !== SaleStatus::Completed) {
            return;
        }

        foreach ($sale->products as $product) {
            $pivot = $product->pivot;

            $lastBalance = $product->transactions()->latest('id')->value('quantity_balance') ?? 0;

            $newProductBalance = $lastBalance - $pivot->quantity;

            $product->transactions()
                ->create([
                    'store_id' => $sale->store_id,
                    'referenceable_type' => Sale::class,
                    'referenceable_id' => $sale->id,
                    'type' => ($pivot->quantity > 0) ? 'product_stock_out' : 'product_stock_in',
                    'quantity' => $pivot->quantity * -1,
                    'quantity_balance' => $newProductBalance,
                    'note' => ($pivot->quantity > 0) ? 'Stock out from sale' : 'Stock in from return',
                ]);

            $product->stock = $newProductBalance;
            $product->save();
        }

        if ($sale->payment_status === SalePaymentStatus::Credit && $sale->customer) {
            $customer = $sale->customer;

            $lastBalance = $customer->transactions()->latest('id')->value('amount_balance') ?? 0;
            $amount = abs($sale->total);
            $newBalance = $lastBalance + $amount; // or - $amount, depending on transaction type

            $customer->transactions()
                ->create([
                    'store_id' => $sale->store_id,
                    'referenceable_type' => Sale::class,
                    'referenceable_id' => $sale->id,
                    'type' => 'customer_debit',
                    'amount' => $amount,
                    'amount_balance' => $newBalance,
                    'note' => 'Sale: customer debit',
                ]);
        }
    }
}
