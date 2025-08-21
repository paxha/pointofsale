<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierInfolist
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
                                    TextEntry::make('phone'),
                                    TextEntry::make('email'),
                                    TextEntry::make('address'),
                                ])
                                ->columns()
                                ->columnSpanFull(),
                        ])->columnSpan(2),
                        Grid::make()->schema([
                            Section::make('Status')
                                ->schema([
                                    TextEntry::make('status')->badge(),
                                ])
                                ->columnSpanFull(),
                            Section::make('Extras')
                                ->schema([
                                    TextEntry::make('deleted_at')->dateTime(),
                                    TextEntry::make('created_at')->dateTime(),
                                    TextEntry::make('updated_at')->dateTime(),
                                ])
                                ->columnSpanFull(),
                        ])->columnSpan(1),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}
