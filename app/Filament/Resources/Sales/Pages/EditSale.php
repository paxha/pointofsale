<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\SaleResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $sale = $this->getRecord();
        $sale->loadMissing('products');

        $products = [];
        foreach ($sale->products as $product) {
            $pivot = $product->pivot;
            $quantity = $pivot->quantity;
            $unitPrice = $pivot->unit_price;
            $tax = $pivot->tax;
            $discount = $pivot->discount;
            $supplierPrice = $pivot->supplier_price;
            $total = $unitPrice * $quantity * (1 - ($discount / 100));

            $products[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax' => $tax,
                'discount' => $discount,
                'total' => round($total, 2),
                'supplier_price' => $supplierPrice,
            ];
        }

        $subtotal = array_sum(array_map(static fn ($item) => (float) $item['total'], $products));
        $totalTax = array_sum(array_map(static fn ($item) => (float) $item['tax'] * (int) $item['quantity'], $products));
        $discountPercent = min(100, max(0, (float) ($data['discount'] ?? 0)));
        $total = $subtotal * (1 - ($discountPercent / 100));

        $data['products'] = $products;
        $data['subtotal'] = round($subtotal, 2);
        $data['total_tax'] = round($totalTax, 2);
        $data['discount'] = $discountPercent;
        $data['total'] = round($total, 2);
        $data['sale_id'] = $sale->id;

        return $data;
    }
}
