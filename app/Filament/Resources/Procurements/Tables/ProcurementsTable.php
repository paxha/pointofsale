<?php

namespace App\Filament\Resources\Procurements\Tables;

use App\Enums\ProcurementStatus;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProcurementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->prefix('#')
                    ->searchable(),
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('total_requested_supplier_price')
                    ->label('Req. Supplier Price')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('total_received_supplier_price')
                    ->label('Rec. Supplier Price')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->hiddenLabel(),
                EditAction::make()
                    ->hiddenLabel()
                    ->visible(fn ($record) => $record->status !== ProcurementStatus::Closed),
                Action::make('close')
                    ->label('Receive')
                    ->icon(Heroicon::Check)
                    ->url(fn ($record) => route('filament.store.resources.procurements.close', [
                        'tenant' => filament()->getTenant(),
                        'record' => $record,
                    ]))
                    ->visible(fn ($record) => $record->status !== ProcurementStatus::Closed),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
