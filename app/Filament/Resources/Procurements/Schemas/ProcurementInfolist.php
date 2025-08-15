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
                        Grid::make()->schema([
                            Section::make('Details')
                                ->schema([
                                    TextEntry::make('name'),
                                    TextEntry::make('status')->badge(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                            Section::make('Totals')
                                ->schema([
                                    TextEntry::make('total_requested_quantity')->label('Requested qty'),
                                    TextEntry::make('total_received_quantity')->label('Received qty'),
                                    TextEntry::make('total_requested_cost_price')->label('Requested cost')->money('PKR', decimalPlaces: 0),
                                    TextEntry::make('total_requested_tax_amount')->label('Requested tax')->money('PKR', decimalPlaces: 0),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                            Section::make('Requested items')
                                ->schema([
                                    RepeatableEntry::make('procurementProducts')
                                        ->schema([
                                            TextEntry::make('product.name')->label('Product'),
                                            TextEntry::make('requested_quantity')->label('Qty'),
                                            TextEntry::make('requested_unit_price')->label('Unit price')->money('PKR', decimalPlaces: 0),
                                            TextEntry::make('requested_tax_percentage')->label('Tax %'),
                                            TextEntry::make('requested_cost_price')->label('Cost price')->money('PKR', decimalPlaces: 0),
                                        ])
                                        ->columns(5)
                                        ->contained(false),
                                ])
                                ->columnSpanFull(),
                        ])->columnSpan(2),
                        Grid::make()->schema([
                            Section::make('Meta')
                                ->schema([
                                    TextEntry::make('deleted_at')->dateTime(),
                                    TextEntry::make('created_at')->dateTime(),
                                    TextEntry::make('updated_at')->dateTime(),
                                ])
                                ->columns(1)
                                ->columnSpanFull(),
                        ])->columnSpan(1),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}
