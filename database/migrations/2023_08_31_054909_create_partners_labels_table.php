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
        Schema::create('partners_labels', function (Blueprint $table) {
            $table->unsignedBigInteger('labels_id');
            $table->unsignedBigInteger('partners_id');

            $table->timestamps();
            //$table->softDeletes();

            $table->foreign('labels_id')
                ->references('id')->on('labels')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('partners_id')
                ->references('id')->on('partners')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners_labels');
    }
};
