<?php

namespace App\Filament\Resources\Transactions\Tables;

use App\Filament\Resources\Procurements\ProcurementResource;
use App\Filament\Resources\Products\RelationManagers\TransactionsRelationManager;
use App\Filament\Resources\Sales\SaleResource;
use App\Models\Procurement;
use App\Models\Sale;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
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
                    ->color(fn ($record) => ($record->type === 'customer_credit' || $record->type === 'supplier_debit') ? 'success' : 'danger')
                    ->hiddenOn(TransactionsRelationManager::class),
                TextColumn::make('amount_balance')
                    ->label('Balance')
                    ->money('PKR')
                    ->hiddenOn(TransactionsRelationManager::class),
                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->icon(fn ($record) => ($record->type === 'product_stock_in') ? Heroicon::OutlinedArrowDown : Heroicon::OutlinedArrowUp)
                    ->iconColor(fn ($record) => ($record->type === 'product_stock_in') ? 'success' : 'danger')
                    ->color(fn ($record) => ($record->type === 'product_stock_in') ? 'success' : 'danger')
                    ->visibleOn(TransactionsRelationManager::class),
                TextColumn::make('quantity_balance')
                    ->label('Balance')
                    ->visibleOn(TransactionsRelationManager::class),
            ])
            ->defaultSort('id', 'desc');
    }
}
