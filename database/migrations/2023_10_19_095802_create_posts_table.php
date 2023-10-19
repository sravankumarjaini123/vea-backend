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
        if (Schema::hasTable('posts')) {
            Schema::dropIfExists('posts');
        }

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->text('introduction')->nullable();
            $table->longText('description')->nullable();
            $table->enum('post_type',['image','audio','video']);
            $table->unsignedBigInteger('post_file_id')->nullable();
            $table->unsignedBigInteger('post_thumbnail_id')->nullable();
            $table->unsignedBigInteger('status_id')->nullable()->unsigned();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('inactive_at')->nullable();
            $table->boolean('top_news')->default(0);
            $table->timestamp('top_news_expiration')->nullable();
            $table->boolean('visible_as_post')->default(1);
            // Additional Connections
            $table->json('media')->nullable();
            $table->json('galleries')->nullable();
            $table->json('related_posts')->nullable();
            // Shareable Details of Post
            $table->boolean('shareable_posts')->default(0);
            $table->string('shareable_type')->nullable();
            $table->mediumText('shareable_description')->nullable();
            $table->longText('shareable_callback_url')->nullable();
            // Meta Details
            $table->string('seo_tag',255)->nullable();
            $table->string('seo_permalink',255)->nullable();
            $table->text('seo_description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->foreign('post_file_id')->references('id')->on('folders_files')
                ->onDelete('set null')->onUpdate('cascade');

            $table->foreign('post_thumbnail_id')->references('id')->on('folders_files')
                ->onDelete('set null')->onUpdate('cascade');

            $table->foreign('status_id')->references('id')->on('status')
                ->onDelete('set null')->onUpdate('cascade');
        });

        DB::table('resources')->insert([
            'name' => 'Posts',
            'slug' => 'posts',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
