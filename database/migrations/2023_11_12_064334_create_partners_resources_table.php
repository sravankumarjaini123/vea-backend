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
        Schema::create('partners_resources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partners_id');
            $table->unsignedBigInteger('resources_id');
            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->foreign('partners_id')->references('id')->on('partners')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('resources_id')->references('id')->on('resources')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners_resources');
    }
};
