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
            $table->string('source');
            $table->json('params');
            $table->json('response')->nullable();
            $table->string('status');

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
