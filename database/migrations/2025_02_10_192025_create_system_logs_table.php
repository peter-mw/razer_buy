<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source',500)->nullable();
            $table->json('params')->nullable();
            $table->json('response')->nullable();
            $table->string('status',500)->nullable();

            // Indexes
            $table->index('source');
            $table->index('status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
