<?php

namespace App\Filament\Resources\Procurements\Pages;

use App\Enums\ProcurementStatus;
use App\Filament\Resources\Procurements\ProcurementResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewProcurement extends ViewRecord
{
    protected static string $resource = ProcurementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn() => $this->record->status !== ProcurementStatus::Closed),
            Action::make('close')
                ->label('Mark as Closed')
                ->icon(Heroicon::Check)
                ->color('success')
                ->url(fn() => route('filament.store.resources.procurements.close', [
                    'tenant' => filament()->getTenant(),
                    'record' => $this->record,
                ]))
                ->visible(fn() => $this->record->status !== ProcurementStatus::Closed),
        ];
    }
}
