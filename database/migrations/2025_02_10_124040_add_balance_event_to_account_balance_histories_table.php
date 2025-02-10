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
        Schema::table('account_balance_histories', function (Blueprint $table) {
            $table->string('balance_event')->nullable()->after('balance_update_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_balance_histories', function (Blueprint $table) {
            $table->dropColumn('balance_event');
        });
    }
};
