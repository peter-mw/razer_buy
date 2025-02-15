<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->decimal('topup_balance', 10, 2)->default(0);
            $table->decimal('transaction_balance', 10, 2)->default(0);
            $table->decimal('balance_difference', 10, 2)->default(0);
        });
    }

    public function down()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('topup_balance');
            $table->dropColumn('transaction_balance');
            $table->dropColumn('balance_difference');
        });
    }
};
