<?php

namespace App\Filament\Resources\Procurements\Pages;

use App\Filament\Resources\Procurements\ProcurementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProcurements extends ListRecords
{
    protected static string $resource = ProcurementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
