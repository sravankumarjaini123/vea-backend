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
        if (Schema::hasTable('users_twitters')) {
            Schema::dropIfExists('users_twitters');
        }

        Schema::create('users_twitters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('users_id');
            $table->unsignedBigInteger('twitters_id');
            $table->text('twitter_user_id')->nullable();
            $table->string('username')->nullable();
            $table->longText('profile_picture_url')->nullable();
            $table->longText('access_token')->nullable();
            $table->string('token_type')->nullable();
            $table->longText('refresh_token')->nullable();
            $table->string('auth_type')->nullable();
            $table->unsignedBigInteger('auth_id')->nullable();
            $table->string('shareable_password')->nullable();
            $table->timestamps();

            $table->foreign('users_id')->references('id')->on('users')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('twitters_id')->references('id')->on('twitters')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_twitters');
    }
};
