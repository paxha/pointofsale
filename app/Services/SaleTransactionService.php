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
            $newBalance = $lastBalance + $sale->total;

            $customer->transactions()
                ->create([
                    'store_id' => $sale->store_id,
                    'referenceable_type' => Sale::class,
                    'referenceable_id' => $sale->id,
                    'type' => ($sale->total > 0) ? 'customer_debit' : 'customer_credit',
                    'amount' => $sale->total,
                    'amount_balance' => $newBalance,
                    'note' => ($sale->total > 0) ? 'Sale completed: customer debit' : 'Sale returned: customer credit',
                ]);
        }
    }

    public function handleSaleOnCancelled(Sale $sale): void
    {
        $sale->status = SaleStatus::Cancelled;

        foreach ($sale->products as $product) {
            $pivot = $product->pivot;

            $lastProductBalance = $product->transactions()->latest('id')->value('quantity_balance') ?? 0;
            $reverseQuantity = $pivot->quantity;

            $newProductBalance = $lastProductBalance + $reverseQuantity;

            $product->transactions()->create([
                'store_id' => $sale->store_id,
                'referenceable_type' => Sale::class,
                'referenceable_id' => $sale->id,
                'type' => 'product_stock_in',
                'quantity' => $reverseQuantity,
                'quantity_balance' => $newProductBalance,
                'note' => 'Stock restored on sale cancellation',
            ]);

            $product->stock = $newProductBalance;
            $product->save();
        }

        // Reverse customer transaction if sale was on credit
        if ($sale->payment_status === SalePaymentStatus::Credit && $sale->customer) {
            $customer = $sale->customer;

            $lastCustomerBalance = $customer->transactions()->latest('id')->value('amount_balance') ?? 0;
            $amount = abs($sale->total);

            $newCustomerBalance = $lastCustomerBalance - $amount;

            $customer->transactions()->create([
                'store_id' => $sale->store_id,
                'referenceable_type' => Sale::class,
                'referenceable_id' => $sale->id,
                'type' => 'customer_credit',
                'amount' => -$amount,
                'amount_balance' => $newCustomerBalance,
                'note' => 'Sale cancelled: customer credit reversed',
            ]);

            $sale->payment_status = SalePaymentStatus::Refunded;
        }

        $sale->save();
    }
}
