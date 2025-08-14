<?php

namespace App\Filament\Resources\Sales\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SaleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('Sale Reference'),
                TextEntry::make('customer.name')
                    ->label('Customer'),
                RepeatableEntry::make('products')
                    ->label('Products')
                    ->schema([
                        TextEntry::make('name')->label('Product'),
                        TextEntry::make('pivot.quantity')->label('Qty'),
                        TextEntry::make('pivot.unit_price')->label('Unit Price'),
                        TextEntry::make('pivot.discount')->label('Discount %'),
                    ]),
                TextEntry::make('subtotal')->label('Subtotal'),
                TextEntry::make('discount')->label('Discount %'),
                TextEntry::make('tax')->label('Tax'),
                TextEntry::make('total')->label('Total'),
                TextEntry::make('payment_method')->label('Payment Method'),
            ]);
    }
}
