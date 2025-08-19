<?php

namespace Database\Factories;

use App\Enums\SalePaymentStatus;
use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        $store = Store::inRandomOrder()->first();
        $customer = Customer::inRandomOrder()->first();
        $hasCustomer = $this->faker->boolean(80); // 80% sales have a customer
        $today = Carbon::today();
        $createdAt = $this->faker->dateTimeBetween('-3 months', 'now');
        $isToday = $this->faker->boolean(1); // ~10% of sales are today
        if ($isToday) {
            $createdAt = $this->faker->dateTimeBetween($today, 'now');
        }
        $subtotal = $this->faker->randomFloat(2, 500, 10000);
        $discount = $this->faker->randomFloat(2, 0, 20); // percent
        $tax = $this->faker->randomFloat(2, 0, 15); // percent
        $discountAmount = $subtotal * ($discount / 100);
        $taxAmount = ($subtotal - $discountAmount) * ($tax / 100);
        $total = $subtotal - $discountAmount + $taxAmount;

        return [
            'store_id' => $store?->id,
            'customer_id' => $hasCustomer ? $customer?->id : null,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
            'payment_status' => Arr::random(SalePaymentStatus::cases()),
            'status' => Arr::random(SaleStatus::cases()),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
}
