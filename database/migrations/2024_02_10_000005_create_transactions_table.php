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
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products_to_buy')->cascadeOnDelete();
            $table->decimal('amount', 10, 2)->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->string('transaction_id', 500)->unique()->nullable();

            // Indexes
            $table->index('amount');
            $table->index('transaction_date');
            $table->index('transaction_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
