<?php

namespace Database\Factories;

use App\Enums\SalePaymentStatus;
use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        $store = Store::inRandomOrder()->first();
        $customer = Customer::inRandomOrder()->first();
        $status = $this->faker->randomElement(SaleStatus::cases());
        $paymentStatus = $this->faker->randomElement(SalePaymentStatus::cases());
        $paidAt = $paymentStatus === SalePaymentStatus::Paid ? now() : null;

        // We'll attach products in afterCreating
        return [
            'store_id' => $store?->id,
            'customer_id' => $customer?->id,
            'subtotal' => 0,
            'tax' => 0,
            'discount' => 0,
            'total' => 0,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'paid_at' => $paidAt,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Sale $sale) {
            $products = Product::where('store_id', $sale->store_id)->inRandomOrder()->limit(rand(1, 5))->get();
            $subtotal = 0;
            $tax = 0;
            $discount = 0;
            $pivotData = [];
            foreach ($products as $product) {
                $quantity = rand(1, 5);
                $unitPrice = $product->price;
                $lineDiscount = $this->faker->randomFloat(2, 0, 20);
                $lineTax = $this->faker->randomFloat(2, 0, 15);
                $supplierPrice = $product->supplier_price ?? 0;
                $lineSubtotal = $unitPrice * $quantity * (1 - $lineDiscount / 100);
                $subtotal += $lineSubtotal;
                $tax += $lineTax * $quantity;
                $discount += $lineDiscount;
                $pivotData[$product->id] = [
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'tax' => $lineTax,
                    'discount' => $lineDiscount,
                    'supplier_price' => $supplierPrice,
                ];
            }
            $discount = count($products) ? $discount / count($products) : 0;
            $total = $subtotal * (1 - $discount / 100);
            $sale->products()->attach($pivotData);
            $sale->update([
                'subtotal' => round($subtotal, 2),
                'tax' => round($tax, 2),
                'discount' => round($discount, 2),
                'total' => round($total, 2),
            ]);

            // If payment status is credit, create a customer transaction
            if ($sale->payment_status === SalePaymentStatus::Credit) {
                Transaction::create([
                    'store_id' => $sale->store_id,
                    'transactionable_type' => \App\Models\Customer::class,
                    'transactionable_id' => $sale->customer_id,
                    'referenceable_type' => Sale::class,
                    'referenceable_id' => $sale->id,
                    'type' => 'customer_credit',
                    'amount' => $sale->total,
                    'amount_balance' => $sale->total,
                    'note' => 'Customer credit for sale',
                    'meta' => [
                        'sale_id' => $sale->id,
                    ],
                    'created_at' => $sale->created_at,
                    'updated_at' => $sale->created_at,
                ]);
            }
        });
    }
}
