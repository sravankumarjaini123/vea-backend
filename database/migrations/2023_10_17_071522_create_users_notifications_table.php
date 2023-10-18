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
        if (Schema::hasTable('users_notifications')) {
            Schema::dropIfExists('users_notifications');
        }

        Schema::create('users_notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('users_id')->unsigned();
            $table->unsignedBigInteger('notifications_id')->unsigned();

            $table->foreign('users_id')->references('id')->on('users')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('notifications_id')->references('id')->on('notifications')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_notifications');
    }
};
