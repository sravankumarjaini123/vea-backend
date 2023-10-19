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
        if (Schema::hasTable('posts_authors')) {
            Schema::dropIfExists('posts_authors');
        }

        Schema::create('posts_authors', function (Blueprint $table) {
            $table->unsignedBigInteger('posts_id')->unsigned();
            $table->unsignedBigInteger('authors_id')->unsigned();

            $table->foreign('posts_id')->references('id')->on('posts')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('authors_id')->references('id')->on('authors')
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
        Schema::dropIfExists('posts_authors');
    }
};
