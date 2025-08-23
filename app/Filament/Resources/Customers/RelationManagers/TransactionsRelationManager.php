<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\Transactions\Tables\TransactionsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public function table(Table $table): Table
    {
        return TransactionsTable::configure($table);
    }
}
