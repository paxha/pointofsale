<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class StockLevels extends TableWidget
{
    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn(): Builder => Product::query())
            ->columns([
                TextColumn::make('code')
                    ->label('Product Code')
                ->searchable(),
                TextColumn::make('name')
                    ->label('Name'),
                TextColumn::make('stock')
                    ->label('Quantity')
                    ->sortable()
                    ->suffix(fn ($record) => $record->unit?->symbol),
            ])
            ->defaultSort('stock')
            ->defaultPaginationPageOption(2)
            ->paginationPageOptions([2])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
