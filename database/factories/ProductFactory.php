<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Store;
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

        $categoryId = Category::query()->whereStoreId($storeId)->inRandomOrder()->value('id');

        $price = fake()->numberBetween(100, 1000);

        $taxPercentage = fake()->numberBetween(1, 10);

        return [
            'store_id' => $storeId,
            'category_id' => $categoryId,
            'name' => fake()->words(8, true),
            'description' => fake()->sentence(10),
            'sku' => fake()->unique()->bothify('##########'),
            'barcode' => fake()->unique()->bothify('##########'),
            'price' => $price,
            'tax_percentage' => $taxPercentage,
            'cost_price' => fake()->numberBetween(1, $price),
            'stock' => fake()->numberBetween(0, 100),
            'status' => fake()->randomElement(ProductStatus::class),
        ];
    }
}
