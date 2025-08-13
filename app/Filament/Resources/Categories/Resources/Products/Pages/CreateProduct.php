<?php

namespace App\Filament\Resources\Categories\Resources\Products\Pages;

use App\Filament\Resources\Categories\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}
