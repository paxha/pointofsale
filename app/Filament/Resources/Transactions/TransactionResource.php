<?php

namespace App\Filament\Resources\Transactions;

use App\Models\Transaction;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Resources\Resource;

class TransactionResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Transaction::class;

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
        ];
    }
}
