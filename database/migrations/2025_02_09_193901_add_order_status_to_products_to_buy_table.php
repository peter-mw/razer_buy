<?php

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
        Schema::table('products_to_buy', function (Blueprint $table) {
            $table->enum('order_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products_to_buy', function (Blueprint $table) {
            $table->dropColumn('order_status');
        });
    }
};
