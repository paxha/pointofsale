<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Store;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $storeId = Store::inRandomOrder()->value('id');
        $brandId = Brand::query()->where('store_id', $storeId)->inRandomOrder()->value('id');
        $categoryId = Category::query()->where('store_id', $storeId)->inRandomOrder()->value('id');
        // unit_id: 80% null, 20% valid
        $unitId = fake()->boolean(20) ? Unit::query()->where('store_id', $storeId)->inRandomOrder()->value('id') : null;

        $price = fake()->numberBetween(100, 10000);
        $salePrice = fake()->optional()->numberBetween(50, $price);
        if (! $salePrice) {
            $salePrice = $price;
        }
        $salePercentage = $salePrice < $price ? round((($price - $salePrice) / $price) * 100, 2) : 0;
        $taxPercentage = fake()->optional()->randomFloat(2, 0, 20);
        $taxAmount = $taxPercentage ? intval($price * ($taxPercentage / 100)) : null;
        $supplierPercentage = fake()->optional()->randomFloat(2, 0, 50);
        $supplierPrice = $supplierPercentage ? intval($price * ($supplierPercentage / 100)) : null;
        $stock = fake()->randomFloat(2, 0, 1000);

        // Generate unique sku/barcode per store by prefixing with store id
        $sku = $storeId.'-'.fake()->unique()->bothify('########');
        $barcode = $storeId.'-'.fake()->unique()->bothify('########');

        return [
            'store_id' => $storeId,
            'brand_id' => $brandId,
            'category_id' => $categoryId,
            'unit_id' => $unitId,
            'code' => fake()->optional()->bothify('CODE-####'),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(10),
            'sku' => $sku,
            'barcode' => $barcode,
            'price' => $price,
            'sale_price' => $salePrice,
            'sale_percentage' => $salePercentage,
            'tax_percentage' => $taxPercentage,
            'tax_amount' => $taxAmount,
            'supplier_percentage' => $supplierPercentage,
            'supplier_price' => $supplierPrice,
            'stock' => $stock,
            'status' => fake()->randomElement(ProductStatus::cases()),
        ];
    }
}
