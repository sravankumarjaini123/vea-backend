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
        if (Schema::hasTable('categories')) {
            Schema::dropIfExists('categories');
        }

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->unique();
            $table->integer('display_order');
            $table->string('seo_title')->nullable();
            $table->longText('seo_description')->nullable();
            $table->unsignedBigInteger('seo_picture_id')->nullable();
            $table->boolean('is_visibility')->default(1);

            $table->timestamps();

            $table->foreign('seo_picture_id')->references('id')->on('folders_files')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });

        DB::table('resources')->insert([
            'name' => 'Categories',
            'slug' => 'categories',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
