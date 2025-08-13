<?php

namespace App\Filament\Resources\Sales\Schemas;

use App\Enums\SalePaymentMethod;
use App\Filament\Resources\Sales\SaleResource;
use App\Filament\Store\Resources\Users\Schemas\UserForm;
use App\Models\Product;
use App\Models\Sale;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class SaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        Grid::make()
                            ->schema([
                                Flex::make([
                                    TextInput::make('search')
                                        ->hiddenLabel()
                                        ->placeholder('Scan SKU / Barcode')
                                        ->suffixIcon(Heroicon::ViewfinderCircle)
                                        ->autofocus()
                                        ->live()
                                        ->afterStateUpdated(function ($state, $set, $get) {
                                            $product = Product::whereSku($state)->first();
                                            if (!$product) {
                                                Notification::make()
                                                    ->title('Product not found')
                                                    ->danger()
                                                    ->duration(1000)
                                                    ->send();

                                                $set('search', '');
                                                return;
                                            }

                                            SaleForm::upsertProduct($get, $set, $product);
                                            $set('search', '');
                                        }),

                                    Select::make('product_picker')
                                        ->hiddenLabel()
                                        ->placeholder('Search by name, SKU, or barcode')
                                        ->searchable()
                                        ->reactive()
                                        ->getSearchResultsUsing(function (string $search): array {
                                            return Product::query()
                                                ->where('name', 'like', "%{$search}%")
                                                ->orWhere('sku', 'like', "%{$search}%")
                                                ->orWhere('barcode', 'like', "%{$search}%")
                                                ->limit(50)
                                                ->pluck('name', 'id')
                                                ->all();
                                        })
                                        ->afterStateUpdated(function ($state, $set, $get) {
                                            if (!$state) {
                                                return;
                                            }

                                            $product = Product::find($state);
                                            if (!$product) {
                                                Notification::make()
                                                    ->title('Product not found')
                                                    ->danger()
                                                    ->duration(1000)
                                                    ->send();
                                                $set('product_picker', null);
                                                return;
                                            }

                                            SaleForm::upsertProduct($get, $set, $product);
                                            $set('product_picker', null);
                                        }),
                                ])
                                    ->columnSpanFull(),

                                Section::make('Products')
                                    ->schema([
                                        Repeater::make('products')
                                            ->hiddenLabel()
                                            ->default([])
                                            ->deletable()
                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                SaleForm::recalcSummary($get, $set);
                                            })
                                            ->table([
                                                Repeater\TableColumn::make('Product'),
                                                Repeater\TableColumn::make('Qty')
                                                    ->width('100px'),
                                                Repeater\TableColumn::make('Disc. %')
                                                    ->width('100px'),
                                                Repeater\TableColumn::make('Price')
                                                    ->width('100px'),
                                            ])
                                            ->schema([
                                                Hidden::make('product_id'),
                                                TextInput::make('name')
                                                    ->disabled(),
                                                TextInput::make('quantity')
                                                    ->minValue(1)
                                                    ->numeric()
                                                    ->live(debounce: 1000)
                                                    ->afterStateUpdated(function ($state, $set, $get) {
                                                        SaleForm::recalcLine($get, $set);
                                                    }),
                                                TextInput::make('discount')
                                                    ->minValue(0)
                                                    ->maxValue(100)
                                                    ->numeric()
                                                    ->live(debounce: 1000)
                                                    ->afterStateUpdated(function ($state, $set, $get) {
                                                        SaleForm::recalcLine($get, $set);
                                                    }),
                                                TextInput::make('price')
                                                    ->disabled(),
                                            ])
                                            ->addable(false)
                                            ->reorderable(false),
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(2),
                        Grid::make()
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Select::make('customer_id')
                                            ->relationship('customer', 'name')
                                            ->searchable(['name', 'phone'])
                                            ->getOptionLabelFromRecordUsing(fn(Model $record) => "$record->name - $record->phone")
                                            ->createOptionModalHeading('New Customer')
                                    ])
                                    ->columnSpanFull(),
                                Section::make()
                                    ->schema([
                                        TextInput::make('subtotal')
                                            ->numeric()
                                            ->prefix('PKR')
                                            ->inlineLabel()
                                            ->disabled(),
                                        TextInput::make('discount')
                                            ->label('Discount %')
                                            ->prefix('%')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->live(debounce: 1000)
                                            ->inlineLabel()
                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                $discount = min(100, max(0, (float)$state));
                                                $set('discount', $discount);
                                                // Recalculate totals applying global discount
                                                SaleForm::recalcSummary($get, $set);
                                            }),
                                        TextInput::make('total_tax')
                                            ->label('Total Tax')
                                            ->numeric()
                                            ->prefix('PKR')
                                            ->inlineLabel()
                                            ->disabled(),
                                        TextInput::make('total')
                                            ->label('Total Price')
                                            ->numeric()
                                            ->prefix('PKR')
                                            ->inlineLabel()
                                            ->disabled(),
                                    ])
                                    ->columnSpanFull(),
                                Section::make()
                                    ->schema([
                                        Select::make('payment_method')
                                            ->hiddenLabel()
                                            ->options(SalePaymentMethod::class)
                                            ->default(SalePaymentMethod::default())
                                            ->searchable()
                                            ->preload()
                                            ->live(),
                                        TextInput::make('given_cash')
                                            ->label('Given Cash')
                                            ->inlineLabel()
                                            ->numeric()
                                            ->prefix('PKR')
                                            ->live(debounce: 1000)
                                            ->visible(fn($get) => $get('payment_method') === SalePaymentMethod::Cash)
                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                $given = $state;
                                                $total = $get('total');
                                                $set('change', round($given - $total, 2));
                                            }),
                                        TextInput::make('change')
                                            ->numeric()
                                            ->label('Change')
                                            ->inlineLabel()
                                            ->prefix('PKR')
                                            ->disabled()
                                            ->visible(fn($get) => $get('payment_method') === SalePaymentMethod::Cash),
                                        TextInput::make('reference')
                                            ->label('Reference')
                                            ->prefix('#')
                                            ->inlineLabel()
                                            ->visible(fn($get) => $get('payment_method') !== SalePaymentMethod::Cash),
                                    ])
                                    ->columnSpanFull(),
                                Flex::make([
                                    Action::make('checkout')
                                        ->color('info')
                                        ->action(function ($state) {
                                            $products = $state['products'];

                                            if (empty($products)) {
                                                Notification::make()
                                                    ->title('Please add products to the cart.')
                                                    ->danger()
                                                    ->send();

                                                return redirect()->to(SaleResource::getUrl('create'));
                                            }

                                            $sale = Sale::create([
                                                'customer_id' => $state['customer_id'],
                                                'subtotal' => $state['subtotal'],
                                                'discount' => $state['discount'],
                                                'tax' => $state['total_tax'],
                                                'total' => $state['total'],
                                                'payment_method' => $state['payment_method'],
                                            ]);

                                            foreach ($products as $product) {
                                                $sale->products()->attach([
                                                    'product_id' => $product['product_id'],
                                                ], [
                                                    'unit_price' => $product['unit_price'],
                                                    'quantity' => $product['quantity'],
                                                    'price' => $product['price'],
                                                    'tax' => $product['tax'],
                                                    'discount' => $product['discount'],
                                                ]);
                                            }

                                            Notification::make()
                                                ->title('Sale created successfully')
                                                ->success()
                                                ->send();

                                            return redirect()->route('sales.receipt', [
                                                'sale' => $sale->id,
                                                'next' => SaleResource::getUrl('create'),
                                            ]);
                                        }),
                                    Action::make('cancel')
                                        ->color('gray')
                                        ->action(function () {
                                            return redirect()->to(SaleResource::getUrl('create'));
                                        }),
                                ]),
                            ])
                    ])
                    ->columns(3)
                    ->columnSpanFull()
            ]);
    }

    /**
     * Recalculate and set the overall cart total from a products array path.
     */
    private static function recalcSummary(
        callable $get,
        callable $set,
        string   $productsPath = 'products',
        string   $subtotalPath = 'subtotal',
        string   $totalPath = 'total',
        string   $totalTaxPath = 'total_tax',
        string   $discountPath = 'discount',
        string   $givenCashPath = 'given_cash',
        string   $changePath = 'change'
    ): void
    {
        $products = $get($productsPath);

        $subtotal = array_sum(array_map(static function ($item) {
            return $item['price'];
        }, $products));

        $set($subtotalPath, round($subtotal, 2));

        $totalTax = array_sum(array_map(static function ($item) {
            return $item['tax'];
        }, $products));

        $discountPercent = min(100, max(0, $get($discountPath)));
        $total = $subtotal * (1 - ($discountPercent / 100));

        $set($totalPath, round($total, 2));
        $set($totalTaxPath, round($totalTax, 2));

        // Keep change in sync whenever totals change
        $given = $get($givenCashPath);
        $set($changePath, round($given - $total, 2));
    }

    /**
     * Recalculate the repeater item's line price based on quantity and discount,
     * then update the overall summary total.
     */
    private static function recalcLine(
        callable $get,
        callable $set,
        string   $productsPath = '../../products',
        string   $subtotalPath = '../../subtotal',
        string   $totalPath = '../../total',
        string   $totalTaxPath = '../../total_tax',
        string   $discountPath = '../../discount',
        string   $givenCashPath = '../../given_cash',
        string   $changePath = '../../change'
    ): void
    {
        $quantity = max(1, (int)($get('quantity') ?: 1));
        $discount = min(100, max(0, (int)($get('discount') ?? 0)));

        $set('quantity', $quantity);
        $set('discount', $discount);

        $unitPrice = $get('unit_price');

        $lineTotal = $unitPrice * $quantity * (1 - ($discount / 100));

        $set('price', round($lineTotal, 2));
        $set('tax', round($get('unit_tax') * $quantity, 2));

        self::recalcSummary($get, $set, $productsPath, $subtotalPath, $totalPath, $totalTaxPath, $discountPath, $givenCashPath, $changePath);
    }

    /**
     * Insert a product into the cart or increment an existing one, then update the summary.
     */
    private static function upsertProduct(callable $get, callable $set, Product $product): void
    {
        $products = $get('products') ?? [];
        $found = false;

        foreach ($products as $key => &$item) {
            if ($item['product_id'] == $product->id) {
                $item['quantity'] += 1;
                $item['price'] = $item['unit_price'] * $item['quantity'] * (1 - ($item['discount'] / 100));
                $item['tax'] = $item['unit_tax'] * $item['quantity'];

                unset($products[$key]);
                array_unshift($products, $item);
                $found = true;
                break;
            }
        }
        unset($item);

        if (!$found) {
            array_unshift($products, [
                'product_id' => $product->id,
                'name' => $product->name,
                'quantity' => 1,
                'discount' => 0,
                'price' => $product->price,
                'unit_price' => $product->price,
                'tax' => $product->tax_amount,
                'unit_tax' => $product->tax_amount,
            ]);
        }

        $set('products', array_values($products));
        self::recalcSummary($get, $set);
    }
}
