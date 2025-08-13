<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
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
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
        ];
    }
}
