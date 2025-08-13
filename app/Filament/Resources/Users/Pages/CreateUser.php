<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Traits\ResourceHasRedirectUrl;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use ResourceHasRedirectUrl;

    protected static string $resource = UserResource::class;
}
