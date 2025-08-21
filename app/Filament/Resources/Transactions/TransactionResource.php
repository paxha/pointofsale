<?php

namespace App\Filament\Resources\Transactions;

use App\Models\Transaction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')->label('Type')->sortable(),
                Tables\Columns\TextColumn::make('amount')->label('Amount')->money('usd', true)->sortable(),
                Tables\Columns\TextColumn::make('quantity')->label('Quantity')->sortable(),
                Tables\Columns\TextColumn::make('note')->label('Note')->limit(30),
                Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }
}
