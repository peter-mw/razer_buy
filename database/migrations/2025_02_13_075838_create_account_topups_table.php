<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->decimal('topup_amount', 10, 2);
            $table->timestamp('topup_time');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_topups');
    }
};
