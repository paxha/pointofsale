<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Procurements\ProcurementResource;
use App\Filament\Resources\Sales\SaleResource;
use App\Filament\Resources\Suppliers\SupplierResource;
use App\Models\Customer;
use App\Models\Procurement;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\Transaction;
use Filament\Actions\BulkActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestTransactions extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Transaction::query()->whereIn('transactionable_type', [Supplier::class, Customer::class])->latest()->take(10))
            ->columns([
                TextColumn::make('transactionable')
                    ->label('Transaction')
                    ->description(fn ($record) => class_basename($record->transactionable_type), 'above')
                    ->color('primary')
                    ->formatStateUsing(fn ($record) => $record->referenceable?->id)
                    ->url(function ($record) {
                        if ($record->transactionable_type === Supplier::class && $record->transactionable) {
                            return SupplierResource::getUrl('view', ['record' => $record->transactionable]);
                        } elseif ($record->transactionable_type === Customer::class && $record->transactionable) {
                            return CustomerResource::getUrl('view', ['record' => $record->transactionable]);
                        }

                        return null;
                    }),
                TextColumn::make('referenceable')
                    ->label('Reference')
                    ->description(fn ($record) => class_basename($record->referenceable_type), 'above')
                    ->color('primary')
                    ->formatStateUsing(fn ($record) => $record->referenceable?->reference ?? $record->referenceable?->id)
                    ->url(function ($record) {
                        if ($record->referenceable_type === Procurement::class && $record->referenceable) {
                            return ProcurementResource::getUrl('view', ['record' => $record->referenceable]);
                        } elseif ($record->referenceable_type === Sale::class && $record->referenceable) {
                            return SaleResource::getUrl('view', ['record' => $record->referenceable]);
                        }

                        return null;
                    }),
                TextColumn::make('note'),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('PKR')
                    ->icon(fn ($record) => $record->amount > 0 ? Heroicon::OutlinedArrowUp : Heroicon::OutlinedArrowDown)
                    ->iconColor(fn ($record) => ($record->type === 'customer_credit' || $record->type === 'supplier_debit') ? 'success' : 'danger')
                    ->color(fn ($record) => ($record->type === 'customer_credit' || $record->type === 'supplier_debit') ? 'success' : 'danger'),
                TextColumn::make('amount_balance')
                    ->label('Balance')
                    ->money('PKR'),
            ])
            ->paginated(false)
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
