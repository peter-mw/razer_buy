<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('product_face_value', 10, 2)->after('product_buy_value')->default(0);
        });

        Schema::table('products_to_buy', function (Blueprint $table) {
            $table->decimal('product_face_value', 10, 2)->after('buy_value')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('product_face_value');
        });

        Schema::table('products_to_buy', function (Blueprint $table) {
            $table->dropColumn('product_face_value');
        });
    }
};
