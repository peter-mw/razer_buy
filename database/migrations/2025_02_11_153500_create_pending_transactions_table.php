<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pending_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('account_id')->nullable();
            $table->string('product_id')->nullable();
            $table->string('transaction_id')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();

            // Indexes
            $table->index('account_id');
            $table->index('product_id');
            $table->index('transaction_id');
            $table->index('status');
            $table->index('transaction_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_transactions');
    }
};
