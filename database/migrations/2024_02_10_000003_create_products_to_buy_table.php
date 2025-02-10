<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products_to_buy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('product_name', 500)->nullable();
            $table->string('product_edition', 500)->nullable();
            $table->boolean('is_active')->default(true)->nullable();
            $table->string('account_type', 500)->nullable();
            $table->integer('quantity')->default(0)->nullable();
            $table->decimal('buy_value', 10, 2)->nullable();
            $table->decimal('product_face_value', 10, 2)->nullable();
            $table->string('order_status', 500)->nullable();

            // Indexes
            $table->index('product_name');
            $table->index('product_edition');
            $table->index('is_active');
            $table->index('account_type');
            $table->index('quantity');
            $table->index('order_status');
            $table->index(['buy_value', 'product_face_value']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products_to_buy');
    }
};
