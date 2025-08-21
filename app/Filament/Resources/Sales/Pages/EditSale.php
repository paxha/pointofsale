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

        $subtotal = array_sum(array_map(static fn($item) => (float)$item['total'], $products));
        $totalTax = array_sum(array_map(static fn($item) => (float)$item['tax'], $products));
        $discountPercent = min(100, max(0, (float)($data['discount'] ?? 0)));
        $total = $subtotal * (1 - ($discountPercent / 100));

        $data['products'] = $products;
        $data['subtotal'] = round($subtotal, 2);
        $data['total_tax'] = round($totalTax, 2);
        $data['discount'] = $discountPercent;
        $data['total'] = round($total, 2);

        return $data;
    }

    protected function handleRecordUpdate($record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            // Use products from form data, not from the database
            $products = $data['products'] ?? [];

            // Set paid_at if payment_status is 'paid'
            $paidAt = ($data['payment_status'] ?? $record->payment_status) === SalePaymentStatus::Paid ? now() : null;

            // Update sale fields
            $record->update([
                'customer_id' => $data['customer_id'] ?? null,
                'subtotal' => $data['subtotal'] ?? 0,
                'discount' => $data['discount'] ?? 0,
                'tax' => $data['total_tax'] ?? 0,
                'total' => $data['total'] ?? 0,
                'status' => $data['status'] ?? $record->status,
                'payment_status' => $data['payment_status'] ?? $record->payment_status,
                'paid_at' => $paidAt,
            ]);

            // Sync products pivot
            $syncData = [];
            foreach ($products as $item) {
                if (!isset($item['product_id'])) {
                    continue;
                }

                $productId = (int)$item['product_id'];
                $quantity = max(1, (int)($item['quantity'] ?? 1));
                $discount = min(100, max(0, (float)($item['discount'] ?? 0)));
                $unitPrice = (float)($item['unit_price'] ?? 0);
                $lineTax = (float)($item['tax'] ?? 0);
                $supplierPrice = (float)($item['supplier_price'] ?? 0);

                $syncData[$productId] = [
                    'quantity' => $quantity,
                    'unit_price' => round($unitPrice, 2),
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
