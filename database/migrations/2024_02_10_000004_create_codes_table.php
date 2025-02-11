<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete(); // Fixed reference
            $table->string('code', 500)->nullable();
            $table->string('serial_number', 500)->nullable();
            $table->string('product_name', 500)->nullable();
            $table->string('product_edition', 500)->nullable();
            $table->timestamp('buy_date')->nullable();
            $table->decimal('buy_value', 10, 2)->nullable();

            // Indexes
            $table->index('code');
            $table->index('serial_number');
            $table->index('product_name');
            $table->index('product_edition');
            $table->index('buy_date');
            $table->index('buy_value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codes');
    }
};
