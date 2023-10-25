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
        if (Schema::hasTable('twitters_posts')) {
            Schema::dropIfExists('twitters_posts');
        }

        Schema::create('twitters_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('twitters_id');
            $table->unsignedBigInteger('posts_id');
            $table->text('text')->nullable();
            $table->unsignedBigInteger('users_twitters_id')->nullable();
            $table->unsignedBigInteger('users_id')->nullable();
            $table->text('twitter_post_id')->nullable();
            $table->text('tweeted_by')->nullable();
            $table->boolean('retweeted')->nullable();
            $table->json('retweeted_by')->nullable();
            $table->boolean('disconnected')->default(0);

            $table->foreign('twitters_id')->references('id')->on('twitters')
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
        Schema::dropIfExists('twitters_posts');
    }
};
