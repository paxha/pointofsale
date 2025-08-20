<?php

namespace App\Filament\Resources\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Transactions';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')->label('Type')->sortable(),
                Tables\Columns\TextColumn::make('amount')->label('Amount')->money('PKR', true)->sortable(),
                Tables\Columns\TextColumn::make('quantity')->label('Quantity')->sortable(),
                Tables\Columns\TextColumn::make('note')->label('Note')->limit(30),
                Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
