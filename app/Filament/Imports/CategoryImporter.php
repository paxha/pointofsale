<?php

namespace App\Filament\Imports;

use App\Enums\CategoryStatus;
use App\Models\Category;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Validation\Rules\Enum;

class CategoryImporter extends Importer
{
    protected static ?string $model = Category::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Name')
                ->exampleHeader('Name')
                ->example('Bakery'),
            ImportColumn::make('description')
                ->label('Description')
                ->exampleHeader('Description')
                ->example('Freshly baked bread, cakes, and pastries daily'),
            ImportColumn::make('status')
                ->label('Status')
                ->exampleHeader('Status')
                ->example(['active', 'inactive'])
                ->requiredMapping()
                ->rules(['required', new Enum(CategoryStatus::class)]),
        ];
    }

    public function resolveRecord(): Category
    {
        return new Category;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your category import has completed and '.Number::format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
