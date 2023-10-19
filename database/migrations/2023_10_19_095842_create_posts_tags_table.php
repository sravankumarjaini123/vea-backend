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
        if (Schema::hasTable('posts_tags')) {
            Schema::dropIfExists('posts_tags');
        }

        Schema::create('posts_tags', function (Blueprint $table) {
            $table->unsignedBigInteger('posts_id')->unsigned();
            $table->unsignedBigInteger('tags_id')->unsigned();

            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->foreign('posts_id')->references('id')->on('posts')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('tags_id')->references('id')->on('tags')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts_tags');
    }
};
