<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use App\Enums\SupplierStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('address'),
                Select::make('status')
                    ->options(SupplierStatus::class)
                    ->default(SupplierStatus::default())
                    ->required(),
            ]);
    }
}
