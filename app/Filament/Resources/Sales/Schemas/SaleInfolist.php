<?php

namespace App\Filament\Resources\Sales\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SaleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->components([
                        Section::make('Products')
                            ->columnSpan(2)
                            ->components([
                                RepeatableEntry::make('products')
                                    ->label('Products')
                                    ->schema([
                                        Flex::make([
                                            TextEntry::make('name')->label('Product')->columnSpan(3),
                                            TextEntry::make('pivot.unit_price')->label('Unit Price')->money('PKR')->columnSpan(1),
                                            TextEntry::make('pivot.quantity')->label('Qty')->columnSpan(1)
                                                ->color(fn($state) => $state < 0 ? 'danger' : null)
                                                ->badge(fn($state) => $state < 0 ? 'Return' : null),
                                            TextEntry::make('pivot.discount')->label('Disc. %')->columnSpan(1),
                                            TextEntry::make('pivot.tax')->label('Tax')->money('PKR')->columnSpan(1),
                                            TextEntry::make('pivot_total')->label('Line Total')->money('PKR')->columnSpan(1)
                                                ->state(fn($record) =>
                                                    $record->pivot->unit_price * $record->pivot->quantity * (1 - ($record->pivot->discount / 100))
                                                ),
                                        ])->columns(8),
                                    ])
                                    ->contained(false)
                                    ->columnSpanFull(),
                            ]),
                        Grid::make(1)
                            ->columnSpan(1)
                            ->components([
                                Section::make('Order Information')
                                    ->components([
                                        TextEntry::make('id')->label('Reference')->prefix('#'),
                                        TextEntry::make('payment_status')->label('Payment')->badge(),
                                        TextEntry::make('status')->label('Status')->badge(),
                                        TextEntry::make('created_at')->label('Date')->dateTime(),
                                    ])
                                    ->inlineLabel(),
                                Section::make('Customer Information')
                                    ->components([
                                        TextEntry::make('customer.name')->label('Customer'),
                                        TextEntry::make('customer.phone')->label('Phone'),
                                    ])
                                    ->inlineLabel(),
                                Section::make('Summary')
                                    ->components([
                                        TextEntry::make('subtotal')->label('Subtotal')->money('PKR'),
                                        TextEntry::make('discount')->label('Discount')->suffix('%'),
                                        TextEntry::make('tax')->label('Tax')->money('PKR'),
                                        TextEntry::make('total')->label('Total')->money('PKR'),
                                    ])
                                    ->inlineLabel(),
                                Section::make('Payment Details')
                                    ->components([
                                        TextEntry::make('paid_at')->label('Paid Date')->dateTime(),
                                        TextEntry::make('outstanding')->label('Outstanding')->money('PKR')
                                            ->state(fn($record) => $record->total - $record->transactions->where('type', 'customer_credit')->sum('amount')),
                                    ])
                                    ->inlineLabel(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
