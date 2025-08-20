<?php

namespace App\Observers;

use App\Models\Procurement;
use App\Models\Sale;
use App\Models\ProcurementProduct;
use App\Models\ProductSale;
use App\Models\Transaction;

class TransactionObserver
{
    public function created($model): void
    {
        if ($model instanceof Procurement) {
            // Only create transaction when procurement is closed
            if ($model->status->value === 'closed') {
                Transaction::create([
                    'store_id' => $model->store_id,
                    'transactionable_type' => Procurement::class,
                    'transactionable_id' => $model->id,
                    'type' => 'supplier_debit',
                    'amount' => $model->total_received_cost_price,
                    'note' => 'Procurement closed: supplier debit',
                ]);
            }
        }
        if ($model instanceof Sale) {
            // Only create transaction when sale is paid or credit
            if (in_array($model->payment_status->value, ['paid', 'credit'])) {
                Transaction::create([
                    'store_id' => $model->store_id,
                    'transactionable_type' => Sale::class,
                    'transactionable_id' => $model->id,
                    'type' => 'customer_credit',
                    'amount' => $model->total,
                    'note' => 'Sale: customer credit',
                ]);
            }
        }
        // Stock movements
        if ($model instanceof ProcurementProduct) {
            if ($model->received_quantity > 0) {
                Transaction::create([
                    'store_id' => $model->procurement->store_id,
                    'transactionable_type' => ProcurementProduct::class,
                    'transactionable_id' => $model->id,
                    'type' => 'product_stock_in',
                    'amount' => $model->received_unit_price * $model->received_quantity,
                    'quantity' => $model->received_quantity,
                    'note' => 'Stock in from procurement',
                    'meta' => [
                        'product_id' => $model->product_id,
                    ],
                ]);
            }
        }
        if ($model instanceof ProductSale) {
            if ($model->quantity > 0) {
                Transaction::create([
                    'store_id' => $model->sale->store_id,
                    'transactionable_type' => ProductSale::class,
                    'transactionable_id' => $model->id,
                    'type' => 'product_stock_out',
                    'amount' => $model->unit_price * $model->quantity,
                    'quantity' => $model->quantity,
                    'note' => 'Stock out from sale',
                    'meta' => [
                        'product_id' => $model->product_id,
                    ],
                ]);
            }
        }
    }
}
