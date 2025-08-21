<?php

namespace App\Filament\Resources\Procurements\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProcurementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        Grid::make()
                            ->schema([
                                Section::make('General')
                                    ->schema([
                                        TextEntry::make('supplier.name')->label('Supplier'),
                                        TextEntry::make('reference')->label('Reference'),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                                Section::make('Requested items')
                                    ->schema([
                                        RepeatableEntry::make('procurementProducts')
                                            ->schema([
                                                TextEntry::make('product.name')->label('Product')->columnSpan(2),
                                                TextEntry::make('requested_quantity')->label('Qty'),
                                                TextEntry::make('requested_unit_price')->label('Unit price')->money('PKR', decimalPlaces: 0),
                                                TextEntry::make('requested_tax_percentage')->label('Tax %'),
                                                TextEntry::make('requested_tax_amount')->label('Tax amount')->money('PKR', decimalPlaces: 0),
                                                TextEntry::make('requested_supplier_percentage')->label('Sup. %'),
                                                TextEntry::make('requested_supplier_price')->label('Sup. Price')->money('PKR', decimalPlaces: 0),
                                            ])
                                            ->columns(4)
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(2),
                        Grid::make()
                            ->schema([
                                Section::make('Status')
                                    ->schema([
                                        TextEntry::make('status')->badge(),
                                    ])
                                    ->columnSpanFull(),

                                Section::make('Requested Totals')
                                    ->schema([
                                        TextEntry::make('total_requested_quantity')->label('Requested qty'),
                                        TextEntry::make('total_requested_unit_price')->label('Requested unit price')->money('PKR', decimalPlaces: 0),
                                        TextEntry::make('total_requested_tax_amount')->label('Requested tax')->money('PKR', decimalPlaces: 0),
                                        TextEntry::make('total_requested_supplier_price')->label('Requested supplier price')->money('PKR', decimalPlaces: 0),
                                    ])
                                    ->columnSpanFull(),

                                Section::make('Received Totals')
                                    ->schema([
                                        TextEntry::make('total_received_quantity')->label('Received qty'),
                                        TextEntry::make('total_received_unit_price')->label('Received unit price')->money('PKR', decimalPlaces: 0),
                                        TextEntry::make('total_received_tax_amount')->label('Received tax')->money('PKR', decimalPlaces: 0),
                                        TextEntry::make('total_received_supplier_price')->label('Received supplier price')->money('PKR', decimalPlaces: 0),
                                    ])
                                    ->columnSpanFull(),

                                Section::make('Logs')
                                    ->schema([
                                        TextEntry::make('created_at')->dateTime(),
                                        TextEntry::make('updated_at')->dateTime(),
                                        TextEntry::make('deleted_at')->dateTime()->hidden(fn($record) => !$record->deleted_at),
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(1),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}
