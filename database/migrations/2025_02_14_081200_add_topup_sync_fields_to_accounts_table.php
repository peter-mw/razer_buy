<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->timestamp('last_topup_sync_at')->nullable();
            $table->string('last_topup_sync_status')->nullable();
        });
    }

    public function down()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('last_topup_sync_at');
            $table->dropColumn('last_topup_sync_status');
        });
    }
};
