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
        if (Schema::hasTable('wordpress_posts')) {
            Schema::dropIfExists('wordpress_posts');
        }

        Schema::create('wordpress_posts', function (Blueprint $table) {
            $table->unsignedBigInteger('wordpress_id');
            $table->unsignedBigInteger('posts_id');
            $table->unsignedInteger('wp_post_id')->nullable();
            $table->foreign('wordpress_id')->references('id')->on('wordpress')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('posts_id')->references('id')->on('posts')
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
        Schema::dropIfExists('wordpress_posts');
    }
};
