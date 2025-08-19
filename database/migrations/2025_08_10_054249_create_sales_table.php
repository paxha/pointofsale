<?php

use App\Enums\SalePaymentMethod;
use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Store::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Customer::class)->nullable()->constrained()->nullOnDelete();
            $table->integer('subtotal')->nullable();
            $table->integer('discount')->nullable()->comment('Discount in percent');
            $table->integer('tax')->nullable();
            $table->integer('total')->nullable();
            $table->string('payment_method')->default(SalePaymentMethod::default());
            $table->string('status')->default(SaleStatus::Completed);
            $table->timestamps();
        });

        Schema::create('product_sale', function (Blueprint $table) {
            $table->foreignIdFor(Sale::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Product::class)->nullable()->constrained()->nullOnDelete();
            $table->integer('unit_price');
            $table->integer('cost_price')->nullable();
            $table->integer('quantity')->default(1);
            $table->integer('tax')->nullable();
            $table->integer('price');
            $table->integer('discount')->nullable()->comment('Discount in percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_sale');
        Schema::dropIfExists('sales');
    }
};
