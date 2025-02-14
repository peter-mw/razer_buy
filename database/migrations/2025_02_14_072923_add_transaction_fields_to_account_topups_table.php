<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_topups', function (Blueprint $table) {
            $table->string('transaction_ref')->nullable();
            $table->string('transaction_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('account_topups', function (Blueprint $table) {
            $table->dropColumn(['transaction_ref', 'transaction_id']);
        });
    }
};
