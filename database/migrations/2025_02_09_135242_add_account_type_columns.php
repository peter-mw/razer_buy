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
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('account_type')->after('email')->default('standard');
        });

        Schema::table('products_to_buy', function (Blueprint $table) {
            $table->string('account_type')->after('is_active')->default('standard');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('account_type');
        });

        Schema::table('products_to_buy', function (Blueprint $table) {
            $table->dropColumn('account_type');
        });
    }
};
