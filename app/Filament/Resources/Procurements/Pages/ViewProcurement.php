<?php

namespace App\Filament\Resources\Procurements\Pages;

use App\Filament\Resources\Procurements\ProcurementResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProcurement extends ViewRecord
{
    protected static string $resource = ProcurementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
