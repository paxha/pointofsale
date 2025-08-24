<?php

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Store;
use App\Models\Unit;
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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Store::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Brand::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Category::class)->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->integer('price')->nullable();
            $table->integer('sale_price')->nullable();
            $table->decimal('sale_percentage')->nullable();
            $table->decimal('tax_percentage')->nullable();
            $table->integer('tax_amount')->nullable();
            $table->decimal('supplier_percentage')->nullable();
            $table->integer('supplier_price')->nullable();
            $table->decimal('stock')->default(0);
            $table->foreignIdFor(Unit::class)->nullable()->constrained()->restrictOnDelete();
            $table->string('status')->default(ProductStatus::default());
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['store_id', 'sku']);
            $table->unique(['store_id', 'barcode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
