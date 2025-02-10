<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 500)->nullable();
            $table->string('email', 500)->unique(); // Keep required as it's the login identifier
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 500); // Keep required for authentication

            // Indexes
            $table->index('name');
            $table->index('email');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
