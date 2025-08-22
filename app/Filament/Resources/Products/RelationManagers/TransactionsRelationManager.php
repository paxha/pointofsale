<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Resources\Procurements\ProcurementResource;
use App\Models\Procurement;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Transactions';

    public function table(Table $table): Table
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
                    ->label('Quantity')
                    ->money('PKR')
                    ->icon(fn ($record) => $record->amount < 0
                        ? 'heroicon-o-arrow-down'
                        : 'heroicon-o-arrow-up'
                    )
                    ->iconColor(fn ($record) => $record->amount < 0
                        ? 'heroicon-o-arrow-down'
                        : 'heroicon-o-arrow-up'
                    )
                    ->color(fn ($record) => $record->amount < 0
                        ? 'danger'
                        : 'success'
                    ),
                TextColumn::make('balance')
                    ->label('Balance')
                    ->money('PKR')
                    ->icon(fn ($record) => $record->amount < 0
                        ? 'heroicon-o-arrow-down'
                        : 'heroicon-o-arrow-up'
                    )
                    ->iconColor(fn ($record) => $record->amount < 0
                        ? 'heroicon-o-arrow-down'
                        : 'heroicon-o-arrow-up'
                    )
                    ->color(fn ($record) => $record->amount < 0
                        ? 'danger'
                        : 'success'
                    ),
            ])
            ->defaultSort('id', 'desc');
    }
}
