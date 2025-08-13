<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Section::make('User Detail')
                            ->schema([
                                TextInput::make('name')
                                    ->required(),
                                TextInput::make('email')
                                    ->email(),
                                TextInput::make('phone')
                                    ->tel(),
                            ])
                            ->columns()
                            ->columnSpan(2),

                        Grid::make()
                            ->schema([
                                Section::make('Status')
                                    ->schema([
                                        Select::make('status')
                                            ->options(UserStatus::class)
                                            ->default(UserStatus::default())
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                    ])
                                    ->columnSpanFull(),

                                Section::make('Role Access')
                                    ->schema([
                                        Select::make('roles')
                                            ->relationship('roles', 'name')
                                            ->saveRelationshipsUsing(function (Model $record, $state) {
                                                $record->roles()->syncWithPivotValues($state, [config('permission.column_names.team_foreign_key') => getPermissionsTeamId()]);
                                            })
                                            ->multiple()
                                            ->preload()
                                            ->searchable(),
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(1),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
