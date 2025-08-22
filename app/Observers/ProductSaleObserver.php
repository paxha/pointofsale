<?php

namespace App\Observers;

use App\Enums\SaleStatus;
use App\Models\ProductSale;

class ProductSaleObserver
{
    public function created(ProductSale $productSale): void
    {
        $sale = $productSale->sale()->first();
        $product = $productSale->product()->first();
        if (! $sale || ! $product) {
            return;
        }
        if ($sale->status !== SaleStatus::Completed) {
            return;
        }
        if ($productSale->quantity > 0) {
            $product->decrement('stock', $productSale->quantity);
        } elseif ($productSale->quantity < 0) {
            $product->increment('stock', abs($productSale->quantity));
        }
    }

    public function updated(ProductSale $productSale): void
    {
        // No logic here. Stock changes on sale status update are handled in SaleObserver.
    }
}
