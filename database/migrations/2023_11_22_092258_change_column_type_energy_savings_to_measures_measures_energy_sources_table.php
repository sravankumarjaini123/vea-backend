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
        Schema::table('measures_measures_energy_sources', function (Blueprint $table) {
            $table->bigInteger('measures_energy_savings')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('measures_measures_energy_sources', function (Blueprint $table) {
            $table->float('measures_energy_savings', 8, 4)->change();
        });
    }
};
