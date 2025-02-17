<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('catalogs', function (Blueprint $table) {
            $table->text('id')->primary();
            $table->text('title')->nullable();
            $table->text('permalink')->nullable();
            $table->bigInteger('region_id')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('catalogs');
    }
};
