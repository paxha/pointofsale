<?php

namespace App\Filament\Resources\Customers\RelationManagers;

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
                Action::make('receive')
                    ->label('Receive from Customer')
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
                        $cusomter = $livewire->getOwnerRecord();

                        $lastBalance = $cusomter->transactions()->latest('id')->value('amount_balance') ?? 0;
                        $amount = $data['amount'] * -1;
                        $newBalance = $lastBalance + $amount;

                        $cusomter->transactions()
                            ->create([
                                'store_id' => $cusomter->store_id,
                                'type' => 'customer_credit',
                                'amount' => $amount,
                                'note' => 'Customer credit: ' . $data['note'],
                                'amount_balance' => $newBalance,
                            ]);

                        Notification::make()
                            ->title('Customer credited successfully')
                            ->success()
                            ->send();
                    })
                    ->icon(Heroicon::OutlinedArrowDown),
            ]);
    }
}
