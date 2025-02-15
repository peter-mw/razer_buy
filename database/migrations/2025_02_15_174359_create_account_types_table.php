<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique(); // 'usa', 'global', etc.
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default account types
        DB::table('account_types')->insert([
            [
                'name' => 'USA Account',
                'code' => 'usa',
                'description' => 'United States account type',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Global Account',
                'code' => 'global',
                'description' => 'Global account type',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_types');
    }
};
