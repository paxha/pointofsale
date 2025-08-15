<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SupplierInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('phone'),
                TextEntry::make('email')
                    ->label('Email address'),
                TextEntry::make('address'),
                TextEntry::make('status')
                ->badge(),
                TextEntry::make('deleted_at')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
