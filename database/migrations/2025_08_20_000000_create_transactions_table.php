<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->morphs('transactionable');
            $table->nullableMorphs('referenceable');
            $table->string('type'); // supplier_debit, supplier_credit, customer_debit, customer_credit, product_stock_in, product_stock_out
            $table->integer('amount')->nullable(); // cents, use PriceCast
            $table->integer('quantity')->nullable(); // for stock movements
            $table->string('note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
