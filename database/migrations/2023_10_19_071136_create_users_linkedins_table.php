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
        if (Schema::hasTable('users_linkedins')) {
            Schema::dropIfExists('users_linkedins');
        }

        Schema::create('users_linkedins', function (Blueprint $table) {
            $table->unsignedBigInteger('users_id');
            $table->unsignedBigInteger('linkedins_id');
            $table->text('linkedin_user_id')->nullable();
            $table->text('organisation_id')->nullable();
            $table->string('username')->nullable();
            $table->longText('profile_picture_url')->nullable();
            $table->longText('access_token');
            $table->longText('refresh_token');
            $table->string('token_type');

            $table->timestamps();

            $table->foreign('users_id')->references('id')->on('users')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('linkedins_id')->references('id')->on('linkedins')
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
        Schema::dropIfExists('users_linkedins');
    }
};
