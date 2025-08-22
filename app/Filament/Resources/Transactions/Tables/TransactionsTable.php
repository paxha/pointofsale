<?php

namespace App\Filament\Resources\Transactions\Tables;

use App\Filament\Resources\Procurements\ProcurementResource;
use App\Filament\Resources\Products\RelationManagers\TransactionsRelationManager;
use App\Models\Procurement;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('referenceable.reference')
                    ->label('Reference')
                    ->description('Procurement', 'above')
                    ->color('primary')
                    ->url(function ($record) {
                        if ($record->referenceable_type === Procurement::class && $record->referenceable) {
                            return ProcurementResource::getUrl('view', ['record' => $record->referenceable]);
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
                    ->icon(fn($record) => $record->amount > 0 ? Heroicon::OutlinedArrowUp : Heroicon::OutlinedArrowDown)
                    ->iconColor(fn($record) => $record->amount > 0 ? 'success' : 'danger')
                    ->color(fn($record) => $record->amount > 0 ? 'success' : 'danger')
                    ->hiddenOn(TransactionsRelationManager::class),
                TextColumn::make('amount_balance')
                    ->label('Balance')
                    ->money('PKR')
                    ->hiddenOn(TransactionsRelationManager::class),
                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->icon(fn($record) => $record->quantity > 0 ? Heroicon::OutlinedArrowDown : Heroicon::OutlinedArrowUp)
                    ->iconColor(fn($record) => $record->quantity > 0 ? 'success' : 'danger')
                    ->color(fn($record) => $record->quantity > 0 ? 'success' : 'danger')
                    ->visibleOn(TransactionsRelationManager::class),
                TextColumn::make('quantity_balance')
                    ->label('Balance')
                    ->visibleOn(TransactionsRelationManager::class),
            ])
            ->defaultSort('id', 'desc');
    }
}
