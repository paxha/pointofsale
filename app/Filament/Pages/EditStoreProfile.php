<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditStoreProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Store profile';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Store details')
                    ->components([
                        TextInput::make('name'),
                        TextInput::make('phone'),
                        TextInput::make('email'),
                        TextInput::make('address'),
                    ])
                    ->columns(),
            ]);
    }
}
