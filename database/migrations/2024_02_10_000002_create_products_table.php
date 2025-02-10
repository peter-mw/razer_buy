<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_name', 500); // Keep required for product identification
            $table->string('product_slug', 500); // Keep required for URLs
            $table->string('account_type', 500)->nullable();
            $table->string('product_edition', 500)->nullable();
            $table->decimal('product_buy_value', 10, 2)->nullable();
            $table->decimal('product_face_value', 10, 2)->nullable();
            $table->string('remote_crm_product_name', 500)->nullable();

            // Indexes
            $table->index('product_name');
            $table->index('product_slug');
            $table->index('account_type');
            $table->index('product_edition');
            $table->index(['product_buy_value', 'product_face_value']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
