<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Resources;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('measures_processors')) {
            Schema::dropIfExists('measures_processors');
        }
        if (Schema::hasTable('measures_types')) {
            Schema::dropIfExists('measures_types');
        }

        if (Schema::hasTable('measures_categories')) {
            Schema::dropIfExists('measures_categories');
        }

        if (Schema::hasTable('measures_energy_sources')) {
            Schema::dropIfExists('measures_energy_sources');
        }

        Schema::create('measures_processors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('display_order');
            $table->timestamps();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });

        Schema::create('measures_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('display_order');
            $table->timestamps();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });

        Schema::create('measures_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('display_order');
            $table->timestamps();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });

        Schema::create('measures_energy_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->longText('comments')->nullable();
            $table->float('co2_emission_factor', 8, 3)->nullable();
            $table->float('commodity_price', 8, 4)->nullable();
            $table->float('energy_price', 8, 4)->nullable();
            $table->float('charges_and_levies', 8, 4)->nullable();
            $table->timestamps();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });

        if (!Resources::where('slug', 'measure-masterdata')->exists()) {
            Resources::insert([
                'name' => 'Measure MasterData',
                'slug' => 'measure-masterdata',
            ]);
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measures_processors');
        Schema::dropIfExists('measures_types');
        Schema::dropIfExists('measures_categories');
        Schema::dropIfExists('measures_energy_sources');
    }
};
