<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('codes', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->constrained('purchase_orders')->after('account_id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('codes', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');
        });
    }
};
