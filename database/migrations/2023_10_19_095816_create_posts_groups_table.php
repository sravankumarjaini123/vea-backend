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
        if (Schema::hasTable('posts_groups')) {
            Schema::dropIfExists('posts_groups');
        }

        Schema::create('posts_groups', function (Blueprint $table) {
            $table->unsignedBigInteger('posts_id')->unsigned();
            $table->unsignedBigInteger('groups_id')->unsigned();

            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->foreign('posts_id')->references('id')->on('posts')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('groups_id')->references('id')->on('groups')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts_groups');
    }
};
