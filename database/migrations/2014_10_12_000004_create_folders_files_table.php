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
        if (Schema::hasTable('folders_files')) {
            Schema::dropIfExists('folders_files');
        }

        Schema::create('folders_files', function (Blueprint $table) {

            $table->id();
            $table->integer('folders_id')->nullable();
            $table->string('name',255);
            $table->bigInteger('size');
            $table->tinyText('type');
            $table->string('hash_name',255)->nullable(false);
            $table->text('file_path')->nullable(false);
            $table->string('store_type')->nullable();
            $table->mediumText('copyright_text')->nullable();
            $table->unsignedBigInteger('duration')->nullable();
            $table->string('resolution')->nullable();
            $table->string('optimizing_status')->nullable();
            $table->unsignedBigInteger('optimized_parent_id')->nullable();
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
        Schema::dropIfExists('folders_files');
    }
};
