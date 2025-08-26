<?php

namespace Database\Factories;

use App\Enums\ProcurementStatus;
use App\Models\Procurement;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Procurement>
 */
class ProcurementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $storeId = Store::query()->inRandomOrder()->value('id');
        $supplierId = Supplier::query()->inRandomOrder()->value('id');
        $status = $this->faker->randomElement([
            ProcurementStatus::Pending,
            ProcurementStatus::Open,
            ProcurementStatus::Closed,
            ProcurementStatus::Rejected,
        ]);
        $requestedQty = $this->faker->numberBetween(1, 100);
        $receivedQty = $status === ProcurementStatus::Closed ? $requestedQty : $this->faker->numberBetween(0, $requestedQty);
        $unitPrice = $this->faker->randomFloat(2, 10, 500);
        $taxAmount = $this->faker->randomFloat(2, 0, 50);
        $supplierPrice = $unitPrice + $taxAmount;
        $supplierPercentage = $this->faker->randomFloat(2, 0, 100);

        return [
            'store_id' => $storeId,
            'supplier_id' => $supplierId,
            'reference' => $this->faker->unique()->uuid(),
            'status' => $status,
            'total_requested_quantity' => $requestedQty,
            'total_received_quantity' => $receivedQty,
            'total_requested_unit_price' => $unitPrice,
            'total_received_unit_price' => $unitPrice,
            'total_requested_tax_amount' => $taxAmount,
            'total_received_tax_amount' => $taxAmount,
            'total_requested_supplier_price' => $supplierPrice,
            'total_received_supplier_price' => $supplierPrice,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function ($procurement) {
            if ($procurement->status === ProcurementStatus::Closed) {

                Transaction::create([
                    'store_id' => $procurement->store_id,
                    'transactionable_type' => Supplier::class,
                    'transactionable_id' => $procurement->supplier_id,
                    'referenceable_type' => Procurement::class,
                    'referenceable_id' => $procurement->id,
                    'type' => 'credit',
                    'amount' => $procurement->total_received_supplier_price,
                    'note' => 'Procurement payment',
                    'created_at' => $procurement->created_at,
                ]);

                Transaction::factory()->create([
                    'store_id' => $procurement->store_id,
                    'transactionable_type' => Supplier::class,
                    'transactionable_id' => $procurement->supplier_id,
                    'referenceable_type' => Procurement::class,
                    'referenceable_id' => $procurement->id,
                    'type' => 'credit',
                    'amount' => $procurement->total_received_supplier_price,
                    'note' => 'Procurement payment',
                    'created_at' => $procurement->created_at,
                ]);
            }
        });
    }
}
