<?php

namespace App\Filament\Resources\Sales\Tables;

use App\Filament\Resources\Sales\SaleResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Reference')
                    ->prefix('#'),
                TextColumn::make('customer_display')
                    ->label('Customer')
                    ->getStateUsing(fn($record) => $record->customer?->name ?? 'Guest')
                    ->description(fn($record) => $record->customer?->phone ?: null)
                    ->searchable(
                        query: function (Builder $query, string $search) {
                            $query->orWhereHas('customer', function ($q) use ($search) {
                                $q->where('name', 'like', "%$search%")
                                    ->orWhere('phone', 'like', "%$search%");
                            });
                        }
                    ),
                TextColumn::make('total')
                    ->label('Amount')
                    ->money('PKR'),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn($record) => route('sales.receipt', [
                        'sale' => $record->id,
                        'next' => SaleResource::getUrl(), // return to sales list after printing
                    ])),
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
