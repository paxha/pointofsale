<?php

namespace Database\Factories;

use App\Enums\BrandStatus;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Brand>
 */
class BrandFactory extends Factory
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
            'name' => fake()->company(),
            'description' => fake()->optional()->sentence(8),
            'status' => fake()->randomElement(BrandStatus::cases()),
        ];
    }
}
