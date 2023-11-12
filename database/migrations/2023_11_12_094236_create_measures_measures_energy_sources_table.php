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
        if (Schema::hasTable('measures_measures_energy_sources')) {
            Schema::dropIfExists('measures_measures_energy_sources');
        }

        Schema::create('measures_measures_energy_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('measures_id');
            $table->unsignedBigInteger('measures_energy_sources_id');
            $table->float('measures_energy_savings', 8 , 4);
            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->foreign('measures_id')->references('id')->on('measures')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('measures_energy_sources_id', 'measures_energy_sources_id_foreign')->references('id')->on('measures_energy_sources')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measures_measures_energy_sources');
    }
};
