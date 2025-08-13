<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Traits\ResourceHasRedirectUrl;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    use ResourceHasRedirectUrl;

    protected static string $resource = CategoryResource::class;
}
