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
        if (Schema::hasTable('linkedins_posts')) {
            Schema::dropIfExists('linkedins_posts');
        }

        Schema::create('linkedins_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('linkedins_id')->nullable();
            $table->unsignedBigInteger('posts_id')->nullable();
            $table->text('linkedin_post_id')->nullable();
            $table->text('share_type')->nullable();
            $table->text('shared_by')->nullable();
            $table->string('subject')->nullable();
            $table->longText('body')->nullable();
            $table->string('visibility')->nullable();
            $table->string('content_type')->nullable();
            $table->text('media_id')->nullable();
            $table->longText('external_url')->nullable();
            $table->boolean('reshared')->nullable();
            $table->json('reshared_by')->nullable();
            $table->boolean('disconnected')->default(0);

            $table->foreign('linkedins_id')->references('id')->on('linkedins')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('posts_id')->references('id')->on('posts')
                ->onDelete('set null')->onUpdate('cascade');
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
        Schema::dropIfExists('linkedins_posts');
    }
};
