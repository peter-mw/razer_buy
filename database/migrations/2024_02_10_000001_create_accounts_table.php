<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 500)->nullable();
            $table->string('email', 500); // Keep required for account identification
            $table->string('password', 500); // Keep required for authentication
            $table->string('otp_seed', 500)->nullable();
            $table->string('account_type', 500); // Keep required for account type identification
            $table->string('vendor', 500)->nullable();
            $table->string('email_password', 500)->nullable();
            $table->decimal('ballance_gold', 10, 2)->default(0)->nullable();
            $table->decimal('ballance_silver', 10, 2)->default(0)->nullable();
            $table->decimal('limit_amount_per_day', 10, 2)->default(0)->nullable();
            $table->timestamp('last_ballance_update_at')->nullable();
            $table->string('last_ballance_update_status', 500)->nullable();
            $table->string('service_code', 500)->nullable();
            $table->string('client_id_login', 500)->nullable();

            // Indexes
            $table->index('name');
            $table->index('email');
            $table->index('account_type');
            $table->index('vendor');
            $table->index(['ballance_gold', 'ballance_silver']);
            $table->index('last_ballance_update_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
