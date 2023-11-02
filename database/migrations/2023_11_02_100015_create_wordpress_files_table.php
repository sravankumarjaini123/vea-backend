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
        if (Schema::hasTable('wordpress_files')) {
            Schema::dropIfExists('wordpress_files');
        }

        Schema::create('wordpress_files', function (Blueprint $table) {
            $table->unsignedBigInteger('wordpress_id');
            $table->unsignedBigInteger('files_id');
            $table->unsignedInteger('wp_file_id');
            $table->foreign('wordpress_id')->references('id')->on('wordpress')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('files_id')->references('id')->on('folders_files')
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
        Schema::dropIfExists('wordpress_files');
    }
};
