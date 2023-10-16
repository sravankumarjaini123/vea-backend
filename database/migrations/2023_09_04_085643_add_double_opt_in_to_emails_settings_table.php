<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('emails_settings')->insert([
            'technologies' => 'system',
            'name' => 'user_double_opt_in',
            'display_name' => 'User Double Opt In',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Code
    }
};
