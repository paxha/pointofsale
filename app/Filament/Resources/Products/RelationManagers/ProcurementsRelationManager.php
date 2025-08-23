<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Resources\Procurements\ProcurementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProcurementsRelationManager extends RelationManager
{
    protected static string $relationship = 'procurements';

    protected static ?string $relatedResource = ProcurementResource::class;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->procurements()->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
