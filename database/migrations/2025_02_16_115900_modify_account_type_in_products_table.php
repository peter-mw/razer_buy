<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // First add the new column
        Schema::table('products', function (Blueprint $table) {
            $table->json('account_types')->nullable();
        });
        
        // Then copy the data
        DB::statement("UPDATE products SET account_types = array_to_json(ARRAY[account_type]) WHERE account_type IS NOT NULL");
        
        // Then drop the old column and rename the new one
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('account_type');
        });
        
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('account_types', 'account_type');
        });
    }

    public function down()
    {
        // First add temporary column
        Schema::table('products', function (Blueprint $table) {
            $table->string('account_type_string')->nullable();
        });
        
        // Copy first value from JSON array
        DB::statement("UPDATE products SET account_type_string = account_type->0 WHERE account_type IS NOT NULL");
        
        // Drop JSON column
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('account_type');
        });
        
        // Create and populate string column
        Schema::table('products', function (Blueprint $table) {
            $table->string('account_type')->nullable();
        });
        
        DB::statement('UPDATE products SET account_type = account_type_string');
        
        // Drop temporary column
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('account_type_string');
        });
    }
};
