<?php

namespace Database\Seeders;

use App\Models\Procurement;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class ProcurementSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure there are suppliers and stores
        if (Supplier::count() === 0) {
            Supplier::factory()->count(10)->create();
        }

        $start = now()->copy()->subMonths(3);
        for ($i = 0; $i < 100; $i++) {
            $date = fake()->dateTimeBetween($start, now());
            $procurement = Procurement::factory()->create();
            $procurement->created_at = $date;
            $procurement->updated_at = $date;
            $procurement->save();
        }
    }
}
