<?php

namespace App\Filament\Imports;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Facades\Filament;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        $statusValues = array_map(fn ($c) => $c->value, ProductStatus::cases());

        return [
            ImportColumn::make('category')
                ->label('Category')
                ->helperText('Category name. If not found in this store, it will be created automatically.')
                ->example('Beverages')
                ->relationship(resolveUsing: function (?string $state, array $options): ?Category {
                    if (blank($state)) {
                        return null;
                    }

                    $storeId = $options['store_id'] ?? Filament::getTenant()?->getKey();
                    if (blank($storeId)) {
                        throw ValidationException::withMessages([
                            'category' => 'Cannot resolve store for this import job. Please start the import from within a store context.',
                        ]);
                    }

                    $name = trim((string) $state);

                    $category = Category::query()
                        ->where('store_id', $storeId)
                        ->where('name', $name)
                        ->first();

                    if ($category) {
                        return $category;
                    }

                    $category = new Category(['name' => $name]);
                    // Set foreign key directly for clarity
                    $category->store_id = $storeId;
                    $category->save();

                    return $category;
                }),

            ImportColumn::make('name')
                ->helperText('Product display name.')
                ->example('Coca-Cola Can 330ml'),

            ImportColumn::make('description')
                ->helperText('Optional description shown on product details.')
                ->example('Refreshing carbonated soft drink in a 330ml can.'),

            ImportColumn::make('sku')
                ->label('SKU')
                ->helperText('Unique per store. Used to update existing products during import.')
                ->example('CK-330-RED'),

            ImportColumn::make('barcode')
                ->helperText('EAN/UPC barcode, unique per store (optional).')
                ->example('8964000090123'),

            ImportColumn::make('price')
                ->label('Price')
                ->numeric(decimalPlaces: 2)
                ->castStateUsing(function (float|string|null $state): ?float {
                    if (blank($state)) {
                        return null;
                    }

                    $state = preg_replace('/[^0-9.]/', '', (string) $state);

                    return round((float) $state, 2);
                })
                ->helperText('Standard price in your currency. Use decimals, e.g., 199.99')
                ->example('199.99'),

            ImportColumn::make('sale_price')
                ->label('Sale Price')
                ->numeric(decimalPlaces: 2)
                ->castStateUsing(function (float|string|null $state): ?float {
                    if (blank($state)) {
                        return null;
                    }

                    $state = preg_replace('/[^0-9.]/', '', (string) $state);

                    return round((float) $state, 2);
                })
                ->helperText('Discounted price. Leave empty if not on sale.')
                ->example('149.50'),

            ImportColumn::make('tax_percentage')
                ->label('Tax %')
                ->integer()
                ->rules(['integer', 'between:0,100'])
                ->helperText('Tax rate percentage. Example: 17 for 17%.')
                ->example('17'),

            ImportColumn::make('cost_price')
                ->label('Cost Price')
                ->numeric(decimalPlaces: 2)
                ->castStateUsing(function (float|string|null $state): ?float {
                    if (blank($state)) {
                        return null;
                    }

                    $state = preg_replace('/[^0-9.]/', '', (string) $state);

                    return round((float) $state, 2);
                })
                ->helperText("Supplier's price. Not visible to customers.")
                ->example('120.00'),

            ImportColumn::make('stock')
                ->requiredMapping()
                ->integer()
                ->rules(['required', 'integer', 'min:0'])
                ->helperText('Current stock on hand. Non-negative integer.')
                ->example('24'),

            ImportColumn::make('status')
                ->requiredMapping()
                ->helperText('One of: '.implode(', ', $statusValues).'.')
                ->example('active')
                ->rules(['required', Rule::in($statusValues)]),
        ];
    }

    public function resolveRecord(): Product
    {
        $storeId = $this->options['store_id'] ?? Filament::getTenant()?->getKey();
        if (blank($storeId)) {
            throw ValidationException::withMessages([
                'store' => 'Cannot resolve store for this import job. Please start the import from within a store context.',
            ]);
        }

        $sku = $this->data['sku'] ?? null;
        $barcode = $this->data['barcode'] ?? null;

        $record = null;

        if (filled($sku)) {
            $record = Product::query()
                ->where('store_id', $storeId)
                ->where('sku', $sku)
                ->first();
        }

        if (! $record && filled($barcode)) {
            $record = Product::query()
                ->where('store_id', $storeId)
                ->where('barcode', $barcode)
                ->first();
        }

        if (! $record) {
            $record = new Product;
        }

        // Ensure the record is always associated with the current store (tenant)
        $record->store_id = $storeId;

        return $record;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product import has completed and '.Number::format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
