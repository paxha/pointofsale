<?php

namespace Database\Factories;

use App\Enums\SupplierStatus;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $storeId = Store::query()->inRandomOrder()->value('id');

        return [
            'store_id' => $storeId,
            'name' => fake()->company(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->companyEmail(),
            'address' => fake()->streetAddress(),
            'status' => fake()->randomElement(SupplierStatus::class),
        ];
    }
}
