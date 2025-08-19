<?php

namespace App\Filament\Resources\Sales\Tables;

use App\Enums\SaleStatus;
use App\Filament\Resources\Sales\SaleResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

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
                    ->getStateUsing(fn ($record) => $record->customer?->name ?? 'Guest')
                    ->description(fn ($record) => $record->customer?->phone ?: null)
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
                TextColumn::make('tax')
                    ->label('Tax')
                    ->money('PKR'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('total')
                    ->summarize([
                        Sum::make()
                            ->query(fn (QueryBuilder $query) => $query->where('status', SaleStatus::Completed))
                            ->money('PKR', 100)
                            ->label('Total Sales'),
                    ]),
                TextColumn::make('tax')
                    ->summarize([
                        Sum::make()
                            ->query(fn (QueryBuilder $query) => $query->where('status', SaleStatus::Completed))
                            ->money('PKR', 100)
                            ->label('Total Tax'),
                    ]),
                TextColumn::make('status')
                    ->summarize(
                        Count::make()
                            ->query(fn (QueryBuilder $query) => $query->where('status', SaleStatus::Completed))
                            ->label('No. of Sales')
                    ),
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
                    ->url(fn ($record) => route('sales.receipt', [
                        'sale' => $record->id,
                        'next' => SaleResource::getUrl(), // return to sales list after printing
                    ])),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
