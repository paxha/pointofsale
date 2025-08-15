<?php

namespace App\Filament\Resources\Procurements\Pages;

use App\Filament\Resources\Procurements\ProcurementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProcurement extends CreateRecord
{
    protected static string $resource = ProcurementResource::class;

    protected function afterCreate(): void
    {
        $this->record->recalculateTotals();
    }
}
