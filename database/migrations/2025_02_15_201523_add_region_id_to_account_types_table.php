<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_types', function (Blueprint $table) {
            $table->unsignedInteger('region_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('account_types', function (Blueprint $table) {
            $table->dropColumn('region_id');
        });
    }
};
