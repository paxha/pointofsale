<?php

namespace App\Filament\Resources\Procurements\Schemas;

use App\Enums\ProcurementStatus;
use App\Models\Product;
use App\Models\Supplier;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProcurementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->schema([
                        Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->searchable(['name', 'email', 'phone'])
                            ->preload()
                            ->live()
                            ->required()
                            ->afterStateUpdated(function (mixed $state, $set, $get): void {
                                $supplier = Supplier::find($state);
                                $supplierId = $supplier->id;
                                $procurementCount = $supplier->procurements()->count();
                                $reference = $supplierId.'-'.str_pad($procurementCount + 1, 2, '0', STR_PAD_LEFT);
                                $set('reference', $reference);
                            }),
                        TextInput::make('reference')
                            ->prefix('#')
                            ->readOnly(),
                        Select::make('status')
                            ->options(ProcurementStatus::class)
                            ->default(ProcurementStatus::default())
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make('Requested Products')
                    ->schema([
                        Repeater::make('procurementProducts')
                            ->hiddenLabel()
                            ->relationship()
                            ->defaultItems(1)
                            ->afterStateHydrated(function ($state, $set, $get) {
                                self::recalcSummary($get, $set);
                            })
                            ->afterStateUpdated(function ($state, $set, $get) {
                                self::recalcSummary($get, $set);
                            })
                            ->columns(4)
                            ->schema([
                                Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->searchable(['name', 'description', 'sku', 'barcode'])
                                    ->preload()
                                    ->reactive()
                                    ->required()
                                    ->afterStateUpdated(function (mixed $state, $set, $get): void {
                                        $product = Product::find($state);
                                        $set('requested_supplier_price', $product->supplier_price ?? 0);
                                        $set('requested_supplier_percentage', $product->supplier_percentage ?? 0);
                                        $set('requested_unit_price', $product->price ?? 0);
                                        $set('requested_tax_percentage', $product->tax_percentage ?? 0);
                                        $set('requested_tax_amount', ($product->price ?? 0) * (($product->tax_percentage ?? 0) / 100));
                                        self::recalcLine($get, $set);
                                    })
                                    ->columnSpan(2),
                                TextInput::make('requested_quantity')
                                    ->default(1)
                                    ->integer()
                                    ->minValue(0)
                                    ->live(debounce: 1000)
                                    ->afterStateUpdated(function (mixed $state, $set, $get): void {
                                        $unitPrice = (float) $get('requested_unit_price');
                                        $taxPercent = (float) $get('requested_tax_percentage');
                                        $taxAmount = $unitPrice * ($taxPercent / 100);
                                        $set('requested_tax_amount', $taxAmount);
                                        self::recalcLine($get, $set);
                                    })
                                    ->label('Quantity'),
                                TextInput::make('requested_unit_price')
                                    ->numeric()
                                    ->live(debounce: 1000)
                                    ->afterStateUpdated(function (mixed $state, $set, $get): void {
                                        $unitPrice = (float) ($state ?? 0);

                                        // --- Supplier (Cost) Calculation ---
                                        $supplierPercent = (float) $get('requested_supplier_percentage');
                                        $supplierPrice = (float) $get('requested_supplier_price');
                                        if ($supplierPercent > 0) {
                                            $supplierPrice = $unitPrice - ($unitPrice * $supplierPercent / 100);
                                            $set('requested_supplier_price', round($supplierPrice, 2));
                                        } elseif ($supplierPrice > 0 && $unitPrice > 0) {
                                            $supplierPercent = (($unitPrice - $supplierPrice) / $unitPrice) * 100;
                                            $set('requested_supplier_percentage', round($supplierPercent, 2));
                                        } else {
                                            $set('requested_supplier_percentage', 0);
                                            $set('requested_supplier_price', 0);
                                        }

                                        // --- Tax Calculation ---
                                        $taxPercent = (float) $get('requested_tax_percentage');
                                        $taxAmount = (float) $get('requested_tax_amount');
                                        if ($taxPercent > 0) {
                                            $taxAmount = $unitPrice * ($taxPercent / 100);
                                            $set('requested_tax_amount', round($taxAmount, 2));
                                        } elseif ($taxAmount > 0 && $unitPrice > 0) {
                                            $taxPercent = ($taxAmount / $unitPrice) * 100;
                                            $set('requested_tax_percentage', round($taxPercent, 2));
                                        } else {
                                            $set('requested_tax_percentage', 0);
                                            $set('requested_tax_amount', 0);
                                        }

                                        self::recalcLine($get, $set);
                                    })
                                    ->prefix('PKR')
                                    ->label('Unit Price'),
                                TextInput::make('requested_tax_percentage')
                                    ->numeric()
                                    ->live(debounce: 1000)
                                    ->afterStateUpdated(function (mixed $state, $set, $get): void {
                                        $unitPrice = (float) $get('requested_unit_price');
                                        $taxAmount = $unitPrice * (($state ?? 0) / 100);
                                        $set('requested_tax_amount', $taxAmount);
                                        self::recalcLine($get, $set);
                                    })
                                    ->prefix('%')
                                    ->label('Tax %'),
                                TextInput::make('requested_tax_amount')
                                    ->numeric()
                                    ->prefix('PKR')
                                    ->live(debounce: 1000)
                                    ->afterStateUpdated(function (mixed $state, $set, $get): void {
                                        $unitPrice = (float) $get('requested_unit_price');
                                        if ($unitPrice > 0) {
                                            $taxPercent = ($state / $unitPrice) * 100;
                                            $set('requested_tax_percentage', round($taxPercent, 2));
                                        } else {
                                            $set('requested_tax_percentage', 0);
                                        }
                                        self::recalcLine($get, $set);
                                    })
                                    ->label('Tax Amount'),
                                TextInput::make('requested_supplier_percentage')
                                    ->numeric()
                                    ->live(debounce: 1000)
                                    ->afterStateUpdated(function (mixed $state, $set, $get): void {
                                        $unitPrice = (float) $get('requested_unit_price');
                                        if ($unitPrice > 0) {
                                            $supplierPrice = $unitPrice - ($unitPrice * ($state ?? 0) / 100);
                                            $set('requested_supplier_price', round($supplierPrice, 2));
                                        } else {
                                            $set('requested_supplier_price', 0);
                                        }
                                        self::recalcLine($get, $set);
                                    })
                                    ->prefix('%')
                                    ->label('Supplier %'),
                                TextInput::make('requested_supplier_price')
                                    ->numeric()
                                    ->live(debounce: 1000)
                                    ->afterStateUpdated(function (mixed $state, $set, $get): void {
                                        $unitPrice = (float) $get('requested_unit_price');
                                        if ($unitPrice > 0) {
                                            $supplierPercent = (($unitPrice - ($state ?? 0)) / $unitPrice) * 100;
                                            $set('requested_supplier_percentage', round($supplierPercent, 2));
                                        } else {
                                            $set('requested_supplier_percentage', 0);
                                        }
                                        self::recalcLine($get, $set);
                                    })
                                    ->prefix('PKR')
                                    ->label('Supplier Price'),
                            ])
                            ->addActionLabel('Add product')
                            ->columnSpanFull(),
                    ])
                    ->columns()
                    ->columnSpanFull(),
                Grid::make()
                    ->components([
                        Section::make('Request Summary')
                            ->schema([
                                TextInput::make('total_requested_quantity')
                                    ->label('Total Quantity')
                                    ->inlineLabel()
                                    ->disabled(),
                                TextInput::make('total_requested_unit_price')
                                    ->label('Total Unit Price')
                                    ->inlineLabel()
                                    ->disabled()
                                    ->prefix('PKR'),
                                TextInput::make('total_requested_tax_amount')
                                    ->label('Total Tax Amount')
                                    ->inlineLabel()
                                    ->disabled()
                                    ->prefix('PKR'),
                                TextInput::make('total_requested_supplier_price')
                                    ->label('Total Supplier Price')
                                    ->inlineLabel()
                                    ->disabled()
                                    ->prefix('PKR'),
                            ]),
                        Section::make('Received Summary')
                            ->schema([
                                TextInput::make('total_received_quantity')
                                    ->label('Total Quantity')
                                    ->inlineLabel()
                                    ->disabled(),
                                TextInput::make('total_received_unit_price')
                                    ->label('Total Unit Price')
                                    ->inlineLabel()
                                    ->disabled()
                                    ->prefix('PKR'),
                                TextInput::make('total_received_tax_amount')
                                    ->label('Total Tax Amount')
                                    ->inlineLabel()
                                    ->disabled()
                                    ->prefix('PKR'),
                                TextInput::make('total_received_cost_price')
                                    ->label('Total Cost Price')
                                    ->inlineLabel()
                                    ->disabled()
                                    ->prefix('PKR'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Recalculate and set the overall requested summary totals from the repeater items.
     */
    private static function recalcSummary(
        callable $get,
        callable $set,
        string $itemsPath = 'procurementProducts',
        string $totalPath = 'total_requested_supplier_price'
    ): void {
        $items = $get($itemsPath) ?? [];

        $sumQty = 0.0;
        $sumSupplier = 0.0; // quantity * supplier price
        $sumUnit = 0.0; // quantity * unit price
        $sumTax = 0.0;  // tax on unit price total

        foreach ($items as $item) {
            $qty = (float) ($item['requested_quantity'] ?? 0);
            $supplier = (float) ($item['requested_supplier_price'] ?? 0);
            $unit = (float) ($item['requested_unit_price'] ?? 0);
            $taxP = (float) ($item['requested_tax_percentage'] ?? 0);

            $sumQty += $qty;
            $sumSupplier += $qty * $supplier;
            $lineUnitTotal = $qty * $unit;
            $sumUnit += $lineUnitTotal;
            $sumTax += $lineUnitTotal * ($taxP / 100);
        }

        // Determine the correct prefix for setting root fields from nested repeater context
        $prefix = str_contains($totalPath, 'total_requested_supplier_price')
            ? str_replace('total_requested_supplier_price', '', $totalPath)
            : '';

        $set($prefix.'total_requested_quantity', round($sumQty, 2));
        $set($prefix.'total_requested_supplier_price', round($sumSupplier, 2));
        $set($prefix.'total_requested_unit_price', round($sumUnit, 2));
        $set($prefix.'total_requested_tax_amount', round($sumTax, 2));
    }

    /**
     * Recalculate the summary totals when a single repeater line changes.
     */
    private static function recalcLine(
        callable $get,
        callable $set,
        string $itemsPath = '../../procurementProducts',
        string $totalPath = '../../total_requested_supplier_price'
    ): void {
        self::recalcSummary($get, $set, $itemsPath, $totalPath);
    }
}
