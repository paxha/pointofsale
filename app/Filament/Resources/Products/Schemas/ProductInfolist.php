<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Grid::make()
                            ->schema([
                                Section::make('General')
                                    ->schema([
                                        TextEntry::make('name'),
                                        TextEntry::make('description'),
                                    ])
                                    ->columns()
                                    ->columnSpanFull(),
                                Section::make('Pricing')
                                    ->schema([
                                        TextEntry::make('price')
                                            ->money('PKR')
                                            ->suffix(fn ($record) => $record->unit ? '/'.$record->unit->symbol : null),
                                        TextEntry::make('sale_price')
                                            ->money('PKR')
                                            ->suffix(fn ($record) => $record->unit ? '/'.$record->unit->symbol : null),
                                        TextEntry::make('sale_percentage')
                                            ->label('Sale Percentage')
                                            ->suffix('%'),
                                        TextEntry::make('tax_percentage')
                                            ->label('Tax Percentage')
                                            ->suffix('%'),
                                        TextEntry::make('tax_amount')
                                            ->money('PKR')
                                            ->suffix(fn ($record) => $record->unit ? '/'.$record->unit->symbol : null),
                                        TextEntry::make('supplier_percentage')
                                            ->label('Supplier Percentage')
                                            ->suffix('%'),
                                        TextEntry::make('supplier_price')->money('PKR')
                                            ->suffix(fn ($record) => $record->unit ? '/'.$record->unit->symbol : null),
                                    ])
                                    ->columns()
                                    ->columnSpanFull(),
                                Section::make('Inventory')
                                    ->schema([
                                        TextEntry::make('sku')->label('SKU'),
                                        TextEntry::make('barcode')->label('Barcode'),
                                        TextEntry::make('stock')
                                            ->label('Quantity')
                                            ->suffix(fn ($record) => $record->unit ? $record->unit->symbol : null),
                                    ])
                                    ->columns()
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(2),
                        Grid::make()->schema([
                            Section::make('Status')
                                ->schema([
                                    TextEntry::make('status')->badge(),
                                ])
                                ->columnSpanFull(),
                            Section::make('Raw Metrial Unit')
                                ->schema([
                                    TextEntry::make('unit')
                                        ->label('Unit')
                                        ->formatStateUsing(fn ($state) => "$state->name ($state->symbol)"),
                                ])
                                ->columnSpanFull(),
                            Section::make('Associations')
                                ->schema([
                                    TextEntry::make('category.name')->label('Category'),
                                ])
                                ->columnSpanFull(),
                            Section::make('Logs')
                                ->schema([
                                    TextEntry::make('created_at')->dateTime(),
                                    TextEntry::make('updated_at')->dateTime(),
                                    TextEntry::make('deleted_at')->dateTime()->hidden(fn ($record) => ! $record->deleted_at),
                                ])
                                ->columnSpanFull(),
                        ])
                            ->columnSpan(1),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
