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
                Grid::make()
                    ->schema([
                        Grid::make()->schema([
                            Section::make()
                                ->schema([
                                    TextEntry::make('name'),
                                    TextEntry::make('description')
                                        ->columnSpanFull(),
                                ])
                                ->columns()
                                ->columnSpanFull(),
                            Section::make('Pricing')
                                ->schema([
                                    TextEntry::make('price')
                                        ->money('PKR', decimalPlaces: 0),
                                    TextEntry::make('cost_price')
                                        ->money('PKR', decimalPlaces: 0)
                                        ->helperText('Customers won\'t see this price.'),
                                ])
                                ->columns()
                                ->columnSpanFull(),
                            Section::make('Inventory')
                                ->schema([
                                    TextEntry::make('sku')
                                        ->label('SKU (Stock Keeping Unit)'),
                                    TextEntry::make('barcode')
                                        ->label('Barcode (ISBN, UPC, GTIN, etc.)'),
                                    TextEntry::make('stock')
                                        ->label('Quantity'),
                                ])
                                ->columns()
                                ->columnSpanFull(),
                        ])
                            ->columnSpan(2),
                        Grid::make()->schema([
                            Section::make('Status')
                                ->schema([
                                    TextEntry::make('status')
                                        ->badge(),
                                ])
                                ->columnSpanFull(),
                            Section::make('Associations')
                                ->schema([
                                    TextEntry::make('category.name')
                                        ->label('Category'),
                                ])
                                ->columnSpanFull(),
                            Section::make('Extras')
                                ->schema([
                                    TextEntry::make('deleted_at')
                                        ->dateTime(),
                                    TextEntry::make('created_at')
                                        ->dateTime(),
                                    TextEntry::make('updated_at')
                                        ->dateTime(),
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
