<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_balance_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->decimal('balance_gold', 10, 2)->default(0)->nullable();
            $table->decimal('balance_silver', 10, 2)->default(0)->nullable();
            $table->string('balance_event')->nullable();
            $table->timestamp('balance_update_time')->nullable();

            // Indexes
            $table->index('balance_event');
            $table->index('balance_update_time');
            $table->index(['balance_gold', 'balance_silver']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_balance_histories');
    }
};
