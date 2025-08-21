<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Enums\SalePaymentStatus;
use App\Filament\Resources\Sales\SaleResource;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
            $quantity = (int) ($pivot->quantity ?? 1);
            $unitTax = $quantity > 0 ? (float) ($pivot->tax / $quantity) : (float) $product->tax_amount;

            $products[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'quantity' => $quantity,
                'discount' => (float) ($pivot->discount ?? 0),
                'price' => (float) ($pivot->price ?? 0),
                'unit_price' => (float) ($pivot->unit_price ?? $product->price),
                'tax' => (float) ($pivot->tax ?? 0),
                'unit_tax' => $unitTax,
            ];
        }

        $subtotal = array_sum(array_map(static fn ($item) => (float) $item['price'], $products));
        $totalTax = array_sum(array_map(static fn ($item) => (float) $item['tax'], $products));
        $discountPercent = min(100, max(0, (float) ($data['discount'] ?? 0)));
        $total = $subtotal * (1 - ($discountPercent / 100));

        $data['products'] = $products;
        $data['subtotal'] = round($subtotal, 2);
        $data['total_tax'] = round($totalTax, 2);
        $data['total'] = round($total, 2);
        $data['editing'] = true;

        return $data;
    }

    protected function handleRecordUpdate($record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $sale = $this->getRecord();
            $sale->loadMissing('products');

            $products = [];
            foreach ($sale->products as $product) {
                $pivot = $product->pivot;
                $quantity = (int) ($pivot->quantity ?? 1);
                $unitTax = $quantity > 0 ? (float) ($pivot->tax / $quantity) : (float) $product->tax_amount;

                $products[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'quantity' => $quantity,
                    'discount' => (float) ($pivot->discount ?? 0),
                    'price' => (float) ($pivot->price ?? 0),
                    'unit_price' => (float) ($pivot->unit_price ?? $product->price),
                    'tax' => (float) ($pivot->tax ?? 0),
                    'unit_tax' => $unitTax,
                ];
            }

            // Calculate total_supplier_price
            $totalSupplierPrice = collect($products)->sum(function ($item) {
                return ($item['supplier_price'] ?? 0) * ($item['quantity'] ?? 1);
            });

            // Set paid_at if payment_status is 'paid'
            $paidAt = ($data['payment_status'] ?? $record->payment_status) === SalePaymentStatus::Paid ? now() : null;

            // Update sale fields
            $record->update([
                'customer_id' => $data['customer_id'] ?? null,
                'subtotal' => $data['subtotal'] ?? 0,
                'discount' => $data['discount'] ?? 0,
                'tax' => $data['total_tax'] ?? 0,
                'total' => $data['total'] ?? 0,
                'payment_status' => $data['payment_status'] ?? $record->payment_status,
                'status' => $data['status'] ?? $record->status,
                'total_supplier_price' => $totalSupplierPrice,
                'paid_at' => $paidAt,
            ]);

            // Sync products pivot
            $syncData = [];
            foreach ($products as $item) {
                if (! isset($item['product_id'])) {
                    continue;
                }

                $productId = (int) $item['product_id'];
                $quantity = max(1, (int) ($item['quantity'] ?? 1));
                $discount = min(100, max(0, (float) ($item['discount'] ?? 0)));
                $unitPrice = (float) ($item['unit_price'] ?? 0);
                $linePrice = (float) ($item['price'] ?? ($unitPrice * $quantity * (1 - ($discount / 100))));
                $unitTax = (float) ($item['unit_tax'] ?? 0);
                $lineTax = (float) ($item['tax'] ?? ($unitTax * $quantity));
                $supplierPrice = (float) ($item['supplier_price'] ?? 0);

                $syncData[$productId] = [
                    'unit_price' => round($unitPrice, 2),
                    'quantity' => $quantity,
                    'price' => round($linePrice, 2),
                    'tax' => round($lineTax, 2),
                    'discount' => $discount,
                    'supplier_price' => $supplierPrice,
                ];
            }

            $record->products()->sync($syncData);

            Notification::make()
                ->title('Sale updated successfully')
                ->success()
                ->send();

            return $record;
        });
    }
}
