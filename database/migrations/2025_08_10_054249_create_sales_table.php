<?php

use App\Enums\SalePaymentStatus;
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
            $table->integer('subtotal')->nullable()->comment('Subtotal before tax and discount. Calculated from product_sale');
            $table->integer('tax')->nullable()->comment('Total tax amount. Calculated from product_sale');
            $table->decimal('discount')->nullable()->comment('Discount on subtotal');
            $table->integer('total')->nullable();
            $table->string('status')->default(SaleStatus::Completed);
            $table->string('payment_status')->default(SalePaymentStatus::default());
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('product_sale', function (Blueprint $table) {
            $table->foreignIdFor(Sale::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Product::class)->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity')->default(1);
            $table->integer('unit_price');
            $table->integer('tax')->nullable();
            $table->decimal('discount')->nullable()->comment('Discount in percent');
            $table->decimal('total')->nullable();
            $table->integer('supplier_price')->nullable();
            $table->integer('supplier_total')->nullable();
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
