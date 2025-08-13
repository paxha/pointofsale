<?php

namespace Database\Seeders;

use App\Enums\StoreStatus;
use App\Enums\UserStatus;
use App\Models\Store;
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

        Store::factory()
            ->hasAttached(User::factory()->count(10))
            ->count(10)
            ->create();
    }
}
