<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductStatus;
use App\Filament\Resources\Brands\Schemas\BrandForm;
use App\Filament\Resources\Categories\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Categories\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Models\Unit;
use Filament\Forms\Components\Select;
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
                                        TextInput::make('code'),
                                        TextInput::make('name')
                                            ->required(),
                                        TextInput::make('description')
                                            ->nullable()
                                            ->columnSpanFull(),
                                    ])
                                    ->columns()
                                    ->columnSpanFull(),
                                Section::make('Pricing')
                                    ->schema([
                                        TextInput::make('price')
                                            ->prefix('PKR')
                                            ->helperText('Base price for this product.')
                                            ->numeric()
                                            ->step(0.01)
                                            ->required()
                                            ->columnSpan(2)
                                            ->suffix(fn ($get) => $get('unit_id') ? '/'.Unit::find($get('unit_id'))?->symbol : null)
                                            ->live(debounce: 1000)
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                $salePercentage = (float) $get('sale_percentage');
                                                if ($state > 0 && $salePercentage !== null && $salePercentage !== '') {
                                                    $salePrice = $state - ($state * ($salePercentage / 100));
                                                    $set('sale_price', round($salePrice, 2));
                                                } elseif ($state > 0) {
                                                    $set('sale_price', null);
                                                }
                                                $salePrice = (float) $get('sale_price');
                                                if ($state > 0 && $salePrice > 0) {
                                                    $percentage = 100 - (($salePrice / $state) * 100);
                                                    $set('sale_percentage', round($percentage, 2));
                                                }
                                            }),
                                        TextInput::make('sale_price')
                                            ->prefix('PKR')
                                            ->helperText('Customers will see this price if you set a sale price.')
                                            ->numeric()
                                            ->step(0.01)
                                            ->columnSpan(2)
                                            ->suffix(fn ($get) => $get('unit_id') ? '/'.Unit::find($get('unit_id'))?->symbol : null)
                                            ->live(debounce: 1000)
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                $price = (float) $get('price');
                                                if ($price > 0 && $state !== null) {
                                                    $percentage = 100 - (($state / $price) * 100);
                                                    $set('sale_percentage', round($percentage, 2));
                                                } elseif ($state === null || $state === '') {
                                                    $set('sale_percentage', null);
                                                }
                                            }),
                                        TextInput::make('sale_percentage')
                                            ->prefix('%')
                                            ->helperText('Or set a sale percentage for this product.')
                                            ->numeric()
                                            ->step(0.01)
                                            ->columnSpan(2)
                                            ->suffix(fn ($get) => $get('unit_id') ? '/'.Unit::find($get('unit_id'))?->symbol : null)
                                            ->live(debounce: 1000)
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                $price = (float) $get('price');
                                                if ($price > 0 && $state !== null) {
                                                    $salePrice = $price - ($price * ($state / 100));
                                                    $set('sale_price', round($salePrice, 2));
                                                } elseif ($state === null || $state === '') {
                                                    $set('sale_price', null);
                                                }
                                            }),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull(),
                                Section::make('Inventory')
                                    ->schema([
                                        TextInput::make('sku')
                                            ->label('SKU (Stock Keeping Unit)'),
                                        TextInput::make('barcode')
                                            ->label('Barcode (ISBN, UPC, GTIN, etc.)'),
                                        TextInput::make('stock')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->default(0)
                                            ->suffix(fn ($get) => $get('unit_id') ? Unit::find($get('unit_id'))?->symbol : null),
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
                                Section::make('Raw Material Unit')
                                    ->schema([
                                        Select::make('unit_id')
                                            ->label('Unit')
                                            ->helperText('The unit of measurement for this raw material.')
                                            ->relationship('unit', 'name')
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "$record->name ($record->symbol)")
                                            ->searchable()
                                            ->preload()
                                            ->reactive(),
                                    ])
                                    ->columnSpanFull(),
                                Section::make('Associations')
                                    ->schema([
                                        Select::make('brand_id')
                                            ->relationship('brand', 'name')
                                            ->createOptionForm(fn (Schema $schema) => BrandForm::configure($schema))
                                            ->searchable()
                                            ->preload(),
                                        Select::make('category_id')
                                            ->relationship('category', 'name')
                                            ->createOptionForm(fn (Schema $schema) => CategoryForm::configure($schema))
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
