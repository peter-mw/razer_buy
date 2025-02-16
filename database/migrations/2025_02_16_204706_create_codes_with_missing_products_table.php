<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('codes_with_missing_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('code');
            $table->string('serial_number');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name');
            $table->string('product_slug');
            $table->string('account_type')->default('unknown');
            $table->string('product_edition')->default('unknown');
            $table->decimal('product_buy_value', 10, 2)->default(0);
            $table->decimal('product_face_value', 10, 2)->default(0);
            $table->datetime('buy_date');
            $table->decimal('buy_value', 10, 2);
            $table->foreignId('order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->string('transaction_ref')->nullable();
            $table->string('transaction_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codes_with_missing_products');
    }
};
