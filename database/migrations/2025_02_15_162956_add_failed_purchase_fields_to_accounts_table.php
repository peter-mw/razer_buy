<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->integer('failed_to_purchase_attempts')->default(0);
            $table->timestamp('failed_to_purchase_timestamp')->nullable();
        });
    }

    public function down()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('failed_to_purchase_attempts');
            $table->dropColumn('failed_to_purchase_timestamp');
        });
    }
};
