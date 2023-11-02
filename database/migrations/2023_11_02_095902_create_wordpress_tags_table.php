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
        if (Schema::hasTable('wordpress_tags')) {
            Schema::dropIfExists('wordpress_tags');
        }

        Schema::create('wordpress_tags', function (Blueprint $table) {
            $table->unsignedBigInteger('wordpress_id');
            $table->unsignedBigInteger('tags_id');
            $table->unsignedInteger('wp_tag_id');
            $table->foreign('wordpress_id')->references('id')->on('wordpress')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('tags_id')->references('id')->on('tags')
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
        Schema::dropIfExists('wordpress_tags');
    }
};
