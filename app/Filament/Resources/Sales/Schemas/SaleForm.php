<?php

namespace App\Filament\Resources\Sales\Schemas;

use App\Enums\SalePaymentStatus;
use App\Enums\SaleStatus;
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use App\Filament\Resources\Sales\SaleResource;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleTransactionService;
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
use Illuminate\Support\Facades\DB;
use Throwable;

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
                                            if (! $product) {
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
                                                ->where('name', 'like', "%$search%")
                                                ->orWhere('sku', 'like', "%$search%")
                                                ->orWhere('barcode', 'like', "%$search%")
                                                ->limit(50)
                                                ->pluck('name', 'id')
                                                ->all();
                                        })
                                        ->afterStateUpdated(function ($state, $set, $get) {
                                            if (! $state) {
                                                return;
                                            }

                                            $product = Product::find($state);
                                            if (! $product) {
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
                                Hidden::make('sale_id'),
                                Repeater::make('products')
                                    ->hiddenLabel()
                                    ->default([])
                                    ->deletable()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        SaleForm::recalcSummary($get, $set);
                                    })
                                    ->table([
                                        Repeater\TableColumn::make('Code')
                                            ->width('100px'),
                                        Repeater\TableColumn::make('Product'),
                                        Repeater\TableColumn::make('Qty')
                                            ->width('110px'),
                                        Repeater\TableColumn::make('Disc. %')
                                            ->width('100px'),
                                        Repeater\TableColumn::make('Total')
                                            ->width('100px'),
                                    ])
                                    ->schema([
                                        Hidden::make('product_id'),
                                        TextInput::make('code')
                                            ->disabled(),
                                        TextInput::make('name')
                                            ->disabled(),
                                        TextInput::make('quantity')
                                            ->rule('not_in:0')
                                            ->placeholder('Use negative for returns')
                                            ->suffix(fn ($get) => $get('unit') ? $get('unit') : null)
                                            ->live(debounce: 1000)
                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                SaleForm::recalcLine($get, $set);
                                            }),
                                        TextInput::make('discount')
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->live(debounce: 1000)
                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                SaleForm::recalcLine($get, $set);
                                            }),
                                        TextInput::make('total')
                                            ->disabled(),
                                    ])
                                    ->addable(false)
                                    ->reorderable(false)
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(7),
                        Grid::make()
                            ->schema([
                                Section::make()
                                    ->schema([
                                        TextInput::make('subtotal')
                                            ->prefix('PKR')
                                            ->inlineLabel()
                                            ->disabled(),
                                        TextInput::make('total_tax')
                                            ->label('Tax')
                                            ->prefix('PKR')
                                            ->inlineLabel()
                                            ->disabled(),
                                        TextInput::make('discount')
                                            ->label('Discount')
                                            ->prefix('%')
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->live(debounce: 1000)
                                            ->inlineLabel()
                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                $discount = min(100, max(0, (float) $state));
                                                $set('discount', $discount);
                                                // Recalculate totals applying global discount
                                                SaleForm::recalcSummary($get, $set);
                                            }),
                                        TextInput::make('total')
                                            ->label('Total')
                                            ->prefix('PKR')
                                            ->inlineLabel()
                                            ->disabled(),
                                    ])
                                    ->columnSpanFull(),
                                Section::make()
                                    ->schema([
                                        Select::make('customer_id')
                                            ->relationship('customer', 'name')
                                            ->searchable(['name', 'phone'])
                                            ->preload()
                                            ->getOptionLabelFromRecordUsing(fn (Model $record) => "$record->name - $record->phone")
                                            ->createOptionForm(fn (Schema $schema) => CustomerForm::configure($schema))
                                            ->createOptionModalHeading('New Customer'),
                                    ])
                                    ->columnSpanFull(),
                                Section::make()
                                    ->schema([
                                        Select::make('payment_status')
                                            ->label('Payment Status')
                                            ->options(SalePaymentStatus::class)
                                            ->default(SalePaymentStatus::default())
                                            ->searchable()
                                            ->preload(),
                                    ])
                                    ->columnSpanFull(),
                                Flex::make([
                                    Action::make('complete')
                                        ->color('primary')
                                        ->label('Complete')
                                        ->action(function ($state) {
                                            return app(static::class)->handleCheckout($state, SaleStatus::Completed);
                                        }),
                                    Action::make('pending')
                                        ->color('warning')
                                        ->label('Pending')
                                        ->action(function ($state) {
                                            return app(static::class)->handleCheckout($state, SaleStatus::Pending);
                                        }),
                                    Action::make('cancel')
                                        ->color('gray')
                                        ->action(function () {
                                            return redirect()->to(SaleResource::getUrl('create'));
                                        }),
                                ]),
                            ])
                            ->columnSpan(3),
                    ])
                    ->columns(10)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Recalculate and set the overall cart total from a products array path.
     */
    private static function recalcSummary(
        callable $get,
        callable $set,
        string $productsPath = 'products',
        string $subtotalPath = 'subtotal',
        string $totalPath = 'total',
        string $totalTaxPath = 'total_tax',
        string $discountPath = 'discount',
    ): void {
        $products = $get($productsPath) ?? [];

        // Subtotal: sum of all (unit_price * quantity * (1 - line discount %))
        $subtotal = array_sum(array_map(static function ($item) {
            $quantity = isset($item['quantity']) ? (float) $item['quantity'] : 1;
            $unitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : 0;
            $discount = isset($item['discount']) ? min(100, max(0, (float) $item['discount'])) : 0;

            return $unitPrice * $quantity * (1 - ($discount / 100));
        }, $products));

        $set($subtotalPath, number_format($subtotal, 2));

        // Total tax: sum of all (tax * quantity)
        $totalTax = array_sum(array_map(static function ($item) {
            $quantity = isset($item['quantity']) ? (float) $item['quantity'] : 1;
            $tax = isset($item['tax']) ? (float) $item['tax'] : 0;

            return $tax * $quantity;
        }, $products));
        $set($totalTaxPath, number_format($totalTax, 2));

        // Sale-level discount (applied to subtotal after line discounts)
        $discountPercent = min(100, max(0, (float) $get($discountPath)));
        $total = $subtotal * (1 - ($discountPercent / 100));
        $set($totalPath, number_format($total, 2));
    }

    /**
     * Recalculate the repeater item's line price based on quantity and discount,
     * then update the overall summary total.
     */
    private static function recalcLine(
        callable $get,
        callable $set,
        string $productsPath = '../../products',
        string $subtotalPath = '../../subtotal',
        string $totalPath = '../../total',
        string $totalTaxPath = '../../total_tax',
        string $discountPath = '../../discount',
    ): void {
        $quantity = (float) ($get('quantity') ?: 1); // Allow negative, disallow zero via validation
        $discount = min(100, max(0, (float) ($get('discount') ?? 0)));

        $set('quantity', $quantity);
        $set('discount', $discount);

        $unitPrice = $get('unit_price');

        $lineTotal = $unitPrice * $quantity * (1 - ($discount / 100));

        $set('total', round($lineTotal, 2));

        self::recalcSummary($get, $set, $productsPath, $subtotalPath, $totalPath, $totalTaxPath, $discountPath);
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
                $item['total'] = $item['unit_price'] * $item['quantity'] * (1 - ($item['discount'] / 100));

                unset($products[$key]);
                array_unshift($products, $item);
                $found = true;
                break;
            }
        }
        unset($item);

        if (! $found) {
            array_unshift($products, [
                'product_id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'quantity' => 1,
                'unit_price' => $product->price,
                'tax' => $product->tax_amount,
                'discount' => $product->sale_percentage,
                'supplier_price' => $product->supplier_price,
                'total' => $product->price * (1 - $product->sale_percentage / 100),
                'unit' => $product->unit?->symbol,
            ]);
        }

        $set('products', array_values($products));
        self::recalcSummary($get, $set);
    }

    /**
     * Recalculate subtotal, tax, discount, and total from the sale's products in the database.
     */
    private static function recalcSaleFromDb(Sale $sale): array
    {
        $products = $sale->products()->withPivot(['quantity', 'unit_price', 'tax', 'discount'])->get();
        $subtotal = 0;
        $totalTax = 0;
        foreach ($products as $product) {
            $quantity = (float) $product->pivot->quantity;
            $unitPrice = (float) $product->pivot->unit_price;
            $discount = min(100, max(0, (float) $product->pivot->discount));
            $tax = (float) $product->pivot->tax;
            $subtotal += $unitPrice * $quantity * (1 - ($discount / 100));
            $totalTax += $tax * $quantity;
        }
        // Use sale-level discount if present, otherwise 0
        $saleDiscount = $sale->discount ?? 0;
        $total = $subtotal * (1 - ($saleDiscount / 100));

        return [
            'subtotal' => round($subtotal, 2),
            'tax' => round($totalTax, 2),
            'discount' => $saleDiscount,
            'total' => round($total, 2),
        ];
    }

    /**
     * Handle the checkout logic, including sale creation and update.
     */
    public static function handleCheckout($state, $status)
    {
        $products = $state['products'] ?? [];
        $subtotal = $state['subtotal'] ?? null;
        $total = $state['total'] ?? null;

        // Validation
        if (empty($products)) {
            Notification::make()
                ->title('Please add products to the cart.')
                ->danger()
                ->send();

            return redirect()->to(SaleResource::getUrl('create'));
        }
        if (! $subtotal || ! $total) {
            Notification::make()
                ->title('Missing required sale information.')
                ->danger()
                ->send();

            return redirect()->to(SaleResource::getUrl('create'));
        }

        try {
            $sale = DB::transaction(function () use ($state, $products, $status) {
                $paidAt = ($state['payment_status'] ?? null) === SalePaymentStatus::Paid ? now() : null;
                if (! empty($state['sale_id'])) {
                    // Update existing sale
                    $sale = Sale::findOrFail($state['sale_id']);
                    $sale->update([
                        'customer_id' => $state['customer_id'] ?? null,
                        'payment_status' => $state['payment_status'] ?? SalePaymentStatus::default(),
                        'status' => $status,
                        'paid_at' => $paidAt,
                    ]);
                    // Sync products
                    $syncData = [];
                    foreach ($products as $product) {
                        $syncData[$product['product_id']] = [
                            'quantity' => $product['quantity'],
                            'unit_price' => $product['unit_price'],
                            'tax' => $product['tax'],
                            'discount' => $product['discount'],
                            'supplier_price' => $product['supplier_price'],
                        ];
                    }
                    $sale->products()->sync($syncData);
                    // Recalculate and update sale summary fields from DB
                    $recalculated = self::recalcSaleFromDb($sale);
                    $sale->update($recalculated);
                } else {
                    // Create new sale
                    $sale = Sale::create([
                        'customer_id' => $state['customer_id'] ?? null,
                        'payment_status' => $state['payment_status'] ?? SalePaymentStatus::default(),
                        'status' => $status,
                        'paid_at' => $paidAt,
                    ]);
                    foreach ($products as $product) {
                        $sale->products()->attach($product['product_id'], [
                            'quantity' => $product['quantity'],
                            'unit_price' => $product['unit_price'],
                            'tax' => $product['tax'],
                            'discount' => $product['discount'],
                            'supplier_price' => $product['supplier_price'],
                        ]);
                    }
                    // Recalculate and update sale summary fields from DB
                    $recalculated = self::recalcSaleFromDb($sale);
                    $sale->update($recalculated);
                }

                return $sale;
            });
        } catch (Throwable $e) {
            Notification::make()
                ->title(($state['sale_id'] ? 'Failed to update sale: ' : 'Failed to create sale: ').$e->getMessage())
                ->danger()
                ->send();

            return redirect()->to($state['sale_id'] ? SaleResource::getUrl('edit', ['record' => $state['sale_id']]) : SaleResource::getUrl('create'));
        }
        Notification::make()
            ->title($state['sale_id'] ? 'Sale updated successfully' : 'Sale created successfully')
            ->success()
            ->send();

        // Only redirect to receipt if status is Completed
        if ($status === SaleStatus::Completed) {
            app(SaleTransactionService::class)->handleSaleOnCompleted($sale);

            return redirect()->route('sales.receipt', [
                'sale' => $sale->id,
                'next' => SaleResource::getUrl('create'),
            ]);
        }

        // Otherwise, just go to create page
        return redirect()->to(SaleResource::getUrl('create'));
    }
}
