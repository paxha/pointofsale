<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Enums\SalePaymentStatus;
use App\Enums\SaleStatus;
use App\Filament\Resources\Sales\SaleResource;
use App\Services\SaleTransactionService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSale extends ViewRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn ($record) => $record->status === SaleStatus::Pending),
            Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->url(fn ($record) => route('sales.receipt', ['sale' => $record->id]))
                ->openUrlInNewTab(),
            Action::make('markAsPaid')
                ->label('Mark as Paid')
                ->icon('heroicon-o-currency-dollar')
                ->visible(fn ($record) => $record->payment_status === SalePaymentStatus::Pending)
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->update([
                        'payment_status' => SalePaymentStatus::Paid,
                        'paid_at' => now(),
                    ]);
                    Notification::make()
                        ->title('Order marked as paid')
                        ->success()
                        ->send();
                }),
            Action::make('markAsCancelled')
                ->label('Mark as Cancelled')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn ($record) => $record->status === SaleStatus::Completed)
                ->requiresConfirmation()
                ->action(function ($record) {
                    app(SaleTransactionService::class)->handleSaleOnCancelled($record);
                    Notification::make()
                        ->title('Order cancelled')
                        ->success()
                        ->send();
                    $this->redirect(SaleResource::getUrl('index'));
                }),
            DeleteAction::make()->visible(fn ($record) => $record->status === SaleStatus::Pending),
        ];
    }
}
