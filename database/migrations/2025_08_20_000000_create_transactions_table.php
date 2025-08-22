<?php

use App\Models\Store;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Store::class)->constrained()->cascadeOnDelete();
            $table->morphs('transactionable');
            $table->nullableMorphs('referenceable');
            $table->string('type'); // supplier_debit, supplier_credit, customer_debit, customer_credit, product_stock_in, product_stock_out
            $table->integer('amount')->nullable();
            $table->integer('amount_balance')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('quantity_balance')->nullable();
            $table->string('note')->nullable();
            $table->json('meta')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
