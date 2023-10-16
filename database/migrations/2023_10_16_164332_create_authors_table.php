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
        if (Schema::hasTable('authors')) {
            Schema::dropIfExists('authors');
        }

        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->integer('profile_photo_id')->nullable()->unsigned();
            $table->boolean('active')->default(0);
            $table->unsignedBigInteger('salutations_id');
            $table->unsignedBigInteger('titles_id')->nullable();

            $table->string('firstname');
            $table->string('lastname');
            $table->string('display_name')->nullable();
            $table->integer('display_order');
            $table->longText('description')->nullable();

            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->foreign('salutations_id')->references('id')->on('salutations')
                ->onUpdate('cascade');
            $table->foreign('titles_id')->references('id')->on('titles')
                ->onUpdate('cascade');
            $table->foreign('profile_photo_id', 'authors_profile_photo_id_foreign')->references('id')->on('folders_files')
                ->onDelete('set null')->onUpdate('cascade');
        });

        DB::table('resources')->insert([
            'name' => 'Authors',
            'slug' => 'authors',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authors');
    }
};
