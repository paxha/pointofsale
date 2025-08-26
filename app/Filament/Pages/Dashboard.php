<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;


class Dashboard extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->components([
                        DatePicker::make('startDate')
                            ->default(now()->subMonth())
                            ->maxDate(fn(Get $get) => $get('endDate') ?: now()),

                        DatePicker::make('endDate')
                            ->default(now())
                            ->minDate(fn(Get $get) => $get('startDate') ?: now())
                            ->maxDate(now()),
                    ])
                    ->columns()
                    ->columnSpanFull(),
            ]);
    }
}
