<?php

namespace Database\Factories;

use App\Enums\CategoryStatus;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $storeId = Store::inRandomOrder()->value('id');

        return [
            'store_id' => $storeId,
            'name' => fake()->word(),
            'description' => fake()->sentence(8),
            'status' => fake()->randomElement(CategoryStatus::class),
        ];
    }
}
