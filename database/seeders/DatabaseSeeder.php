<?php

namespace Database\Seeders;

use App\Enums\StoreStatus;
use App\Enums\UserStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Artisan::call('shield:generate --all --panel=store');

        $user = User::factory()
            ->create([
                'name' => 'Pasha',
                'email' => 'pasha@test.com',
                'status' => UserStatus::Active,
            ]);

        $store = Store::factory()
            ->hasAttached($user)
            ->hasAttached(User::factory()->count(10))
            ->create([
                'name' => 'Pasha',
                'slug' => 'pasha',
                'status' => StoreStatus::Live,
            ]);

        Artisan::call("shield:super-admin --user=$user->id --tenant=$store->id");

        Unit::factory()->createMany([
            ['store_id' => $store->id, 'name' => 'Kilogram', 'symbol' => 'kg'],
            ['store_id' => $store->id, 'name' => 'Liter', 'symbol' => 'l'],
        ]);

        // Seed brands and categories for the store
        //        Brand::factory()->count(10)->for($store)->create();
        //        Category::factory()->count(10)->for($store)->create();

        //        Product::factory()->count(200)->create();

        //        $this->call([
        //            SaleSeeder::class,
        //            ProcurementSeeder::class,
        //        ]);

        //        Store::factory()
        //            ->hasAttached(User::factory()->count(10))
        //            ->count(10)
        //            ->create();
        //
        //        Category::factory()->count(100)->create();
        //
        //        Product::factory()->count(1000)->create();
        //        Customer::factory()->count(1000)->create();
        //
        //        // Seed sales for each store
        //        $stores = Store::all();
        //        $products = Product::all();
        //        $customers = Customer::pluck('id')->toArray();
        //        foreach ($stores as $store) {
        //            Sale::factory()
        //                ->count(200)
        //                ->create(['store_id' => $store->id])
        //                ->each(function ($sale) use ($products) {
        //                    $productCount = rand(1, 5);
        //                    $selectedProducts = $products->random($productCount);
        //                    $pivotData = [];
        //                    foreach ($selectedProducts as $product) {
        //                        $unitPrice = $product->price ?? fake()->randomFloat(2, 100, 1000);
        //                        $quantity = rand(1, 5);
        //                        $discount = fake()->randomFloat(2, 0, 20);
        //                        $tax = fake()->randomFloat(2, 0, 15);
        //                        $price = ($unitPrice * $quantity) * (1 - $discount / 100) * (1 + $tax / 100);
        //                        $pivotData[$product->id] = [
        //                            'unit_price' => $unitPrice,
        //                            'quantity' => $quantity,
        //                            'discount' => $discount,
        //                            'tax' => $tax,
        //                            'price' => $price,
        //                        ];
        //                    }
        //                    $sale->products()->attach($pivotData);
        //                });
        //        }
        //
        //        Supplier::factory()->count(100)->create();
    }
}
