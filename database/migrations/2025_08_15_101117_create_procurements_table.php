<?php

use App\Models\Procurement;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('procurements', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Store::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('total_requested_quantity')->nullable();
            $table->integer('total_received_quantity')->nullable();
            $table->integer('total_requested_unit_price')->nullable();
            $table->integer('total_received_unit_price')->nullable();
            $table->integer('total_requested_tax_amount')->nullable();
            $table->integer('total_received_tax_amount')->nullable();
            $table->integer('total_requested_cost_price')->nullable();
            $table->integer('total_received_cost_price')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('procurement_product', function (Blueprint $table) {
            $table->foreignIdFor(Procurement::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Product::class)->nullable()->constrained()->nullOnDelete();

            $table->integer('requested_quantity')->default(1);
            $table->integer('requested_unit_price')->nullable();
            $table->integer('requested_tax_percentage')->nullable();
            $table->integer('requested_tax_amount')->virtualAs('requested_tax_percentage * requested_unit_price / 100');
            $table->integer('requested_cost_price')->nullable();

            $table->integer('received_quantity')->nullable();
            $table->integer('received_unit_price')->nullable();
            $table->integer('received_tax_percentage')->nullable();
            $table->integer('received_tax_amount')->virtualAs('received_tax_percentage * received_unit_price / 100');
            $table->integer('received_cost_price')->nullable();

            $table->integer('total_requested_unit_price')->virtualAs('requested_quantity * requested_unit_price');
            $table->integer('total_requested_cost_price')->virtualAs('requested_quantity * requested_cost_price');
            $table->integer('total_requested_tax_amount')->virtualAs('requested_quantity * requested_tax_amount');

            $table->integer('total_received_unit_price')->virtualAs('received_quantity * received_unit_price');
            $table->integer('total_received_cost_price')->virtualAs('received_quantity * received_cost_price');
            $table->integer('total_received_tax_amount')->virtualAs('received_quantity * received_tax_amount');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_product');
        Schema::dropIfExists('procurements');
    }
};
