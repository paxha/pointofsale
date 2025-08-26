<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure there are customers and products
        if (Customer::count() === 0) {
            Customer::factory()->count(10)->create();
        }
        if (Product::count() === 0) {
            Product::factory()->count(20)->create();
        }
        $start = now()->copy()->subMonths(3);
        for ($i = 0; $i < 200; $i++) {
            $date = fake()->dateTimeBetween($start, now());
            Sale::factory()->create([
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }
    }
}
