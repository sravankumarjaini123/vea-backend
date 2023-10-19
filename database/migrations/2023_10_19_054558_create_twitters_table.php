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
        if (Schema::hasTable('twitters')) {
            Schema::dropIfExists('twitters');
        }

        Schema::create('twitters', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->unique();
            $table->string('client_id');
            $table->string('client_secret');
            $table->string('callback_url');
            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });
        DB::table('resources')->insert([
            'name' => 'Twitter',
            'slug' => 'twitter',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('twitters');
    }
};
