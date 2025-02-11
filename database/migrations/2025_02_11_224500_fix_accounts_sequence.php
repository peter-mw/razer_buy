<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Get the current maximum ID
        $maxId = DB::table('accounts')->max('id');
        
        // If there are records, reset the sequence
        if ($maxId) {
            DB::statement("ALTER SEQUENCE accounts_id_seq RESTART WITH " . ($maxId + 1));
        }
    }

    public function down()
    {
        // No need for down migration as this is a sequence fix
    }
};
