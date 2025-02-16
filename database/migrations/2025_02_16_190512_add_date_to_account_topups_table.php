<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('account_topups', function (Blueprint $table) {
            $table->date('date')->nullable()->after('topup_time');
        });

        // Fill existing records with date from topup_time
        DB::statement('UPDATE account_topups SET date = DATE(topup_time)');

        // Make the column required after filling data
        Schema::table('account_topups', function (Blueprint $table) {
            $table->date('date')->nullable(false)->change();
        });
    }

    public function down()
    {
        Schema::table('account_topups', function (Blueprint $table) {
            $table->dropColumn('date');
        });
    }
};
