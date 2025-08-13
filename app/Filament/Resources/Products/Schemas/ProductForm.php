<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductStatus;
use App\Filament\Resources\Categories\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Categories\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        Grid::make()
                            ->schema([
                                Section::make()
                                    ->schema([
                                        TextInput::make('name')
                                            ->required(),
                                        TextArea::make('description')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns()
                                    ->columnSpanFull(),
                                Section::make('Pricing')
                                    ->schema([
                                        TextInput::make('price')
                                            ->integer()
                                            ->prefix('PKR')
                                            ->helperText('Customers will see this price.')
                                            ->required()
                                            ->columnSpan(2)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                $taxPercentage = $get('tax_percentage');
                                                $taxAmount = number_format(($state / 100) * $taxPercentage, 2);
                                                $set('tax_amount', $taxAmount);
                                            }),
                                        TextInput::make('tax_percentage')
                                            ->integer()
                                            ->prefix('%')
                                            ->columnSpan(1)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                $price = $get('price');
                                                $taxPercentage = $state;
                                                $taxAmount = number_format(($price / 100) * $taxPercentage, 2);
                                                $set('tax_amount', $taxAmount);
                                            }),
                                        TextInput::make('tax_amount')
                                            ->prefix('PKR')
                                            ->columnSpan(1)
                                            ->readOnly(),
                                        TextInput::make('sale_price')
                                            ->prefix('PKR')
                                            ->helperText('Customers will see this price if you set a sale price.')
                                            ->integer()
                                            ->columnSpan(2),
                                        TextInput::make('cost_price')
                                            ->prefix('PKR')
                                            ->helperText('Supplier\'s price.')
                                            ->columnSpan(2),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull(),
                                Section::make('Inventory')
                                    ->schema([
                                        TextInput::make('sku')
                                            ->label('SKU (Stock Keeping Unit)')
                                            ->required(),
                                        TextInput::make('barcode')
                                            ->label('Barcode (ISBN, UPC, GTIN, etc.)')
                                            ->required(),
                                        TextInput::make('stock')
                                            ->label('Quantity')
                                            ->integer()
                                            ->default(0),
                                    ])
                                    ->columns()
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(2),
                        Grid::make()
                            ->schema([
                                Section::make('Status')
                                    ->schema([
                                        Select::make('status')
                                            ->options(ProductStatus::class)
                                            ->default(ProductStatus::Draft)
                                            ->required(),
                                    ])
                                    ->columnSpanFull(),
                                Section::make('Associations')
                                    ->schema([
                                        Select::make('category_id')
                                            ->relationship('category', 'name')
                                            ->createOptionForm(fn(Schema $schema) => CategoryForm::configure($schema))
                                            ->searchable()
                                            ->preload(),
                                    ])
                                    ->hiddenOn([CreateProduct::class, EditProduct::class])
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(1),

                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}
