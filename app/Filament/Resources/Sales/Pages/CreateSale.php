<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\SaleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function getRedirectUrl(): string
    {
        return self::$resource::getUrl('create');
    }

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
