<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('games', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->bigInteger('product_id')->nullable();
            $table->text('product_code')->nullable();
            $table->text('vanity_name')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('position')->nullable();
            $table->text('category')->nullable();
            $table->bigInteger('merchant_service_id')->nullable();
            $table->bigInteger('merchant_id')->nullable();
            $table->bigInteger('region_id')->nullable();
            $table->boolean('has_stock')->nullable();
            $table->bigInteger('product_type_id')->nullable();
            $table->decimal('unit_gold', 15, 4)->nullable();
            $table->decimal('unit_base_gold', 15, 4)->nullable();
            $table->decimal('unit_tax_gold', 15, 4)->nullable();
            $table->bigInteger('amount_in_rz_silver')->nullable();
            $table->boolean('is_price_incl_tax')->nullable();
            $table->text('tax_name')->nullable();
            $table->bigInteger('tax_rate')->nullable();
            $table->text('product_name')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('games');
    }
};
