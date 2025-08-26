<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'store_id' => null, // Should be set when used
            'transactionable_type' => null, // Should be set when used
            'transactionable_id' => null, // Should be set when used
            'referenceable_type' => null, // Optional, set when needed
            'referenceable_id' => null, // Optional, set when needed
            'type' => $this->faker->randomElement(['debit', 'credit']),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'amount_balance' => null, // Default to null, set in logic if needed
            'quantity' => null, // Default to null, set in logic if needed
            'quantity_balance' => null, // Default to null, set in logic if needed
            'note' => $this->faker->sentence(),
            'meta' => [],
        ];
    }
}
