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
        if (Schema::hasTable('wordpress')) {
            Schema::dropIfExists('wordpress');
        }

        Schema::create('wordpress', function (Blueprint $table) {
            $table->id();
            $table->string('name',255);
            $table->string('site_url')->unique();
            $table->string('username');
            $table->string('password');
            $table->string('cryption_key')->nullable();
            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });

        DB::table('resources')->insert([
            'name' => 'Wordpress',
            'slug' => 'wordpress',
        ]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wordpress');
    }
};
