<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proxy_servers', function (Blueprint $table) {
            $table->id();
            $table->string('proxy_server_ip')->nullable();
            $table->integer('proxy_server_port')->nullable();
            $table->boolean('is_active')->default(true)->nullable();
            $table->timestamp('last_used_time')->nullable();
            $table->json('proxy_account_type')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proxy_servers');
    }
};
