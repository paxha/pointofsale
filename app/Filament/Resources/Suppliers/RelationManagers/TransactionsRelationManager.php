<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use App\Filament\Resources\Transactions\Tables\TransactionsTable;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Flex;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public function table(Table $table): Table
    {
        return TransactionsTable::configure($table)
            ->headerActions([
                Action::make('pay')
                    ->label('Pay to Supplier')
                    ->schema([
                        Flex::make([
                            TextInput::make('amount')
                                ->label('Amount')
                                ->numeric()
                                ->required()
                                ->minValue(0.01),
                            TextInput::make('note')
                                ->label('Note')
                                ->maxLength(20),
                        ])
                    ])
                    ->action(function (array $data, RelationManager $livewire) {
                        $supplier = $livewire->getOwnerRecord();

                        $lastBalance = $supplier->transactions()->latest('id')->value('amount_balance') ?? 0;
                        $amount = $data['amount'];
                        $newBalance = $lastBalance + $amount; // supplier_debit decreases balance

                        $supplier->transactions()
                            ->create([
                                'store_id' => $supplier->store_id,
                                'type' => 'supplier_debit',
                                'amount' => $amount,
                                'note' => 'Supplier debit: ' . $data['note'],
                                'amount_balance' => $newBalance,
                            ]);

                        Notification::make()
                            ->title('Supplier debited successfully')
                            ->success()
                            ->send();
                    })
                    ->color('success')
                    ->icon(Heroicon::OutlinedArrowUp),
            ]);
    }
}
