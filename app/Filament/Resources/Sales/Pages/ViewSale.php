<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\SaleResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSale extends ViewRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->url(fn ($record) => route('sales.receipt', ['sale' => $record->id]))
                ->openUrlInNewTab(),
            Action::make('markAsPaid')
                ->label('Mark as Paid')
                ->icon('heroicon-o-currency-dollar')
                ->visible(fn ($record) => $record->payment_status !== 'paid')
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->update([
                        'payment_status' => 'paid',
                        'paid_at' => now(),
                    ]);
                    $this->notify('success', 'Order marked as paid.');
                }),
        ];
    }
}
