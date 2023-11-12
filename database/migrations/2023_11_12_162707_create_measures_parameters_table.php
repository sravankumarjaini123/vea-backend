<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('measures_parameters')) {
            Schema::dropIfExists('measures_parameters');
        }

        Schema::create('measures_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('key');
            $table->string('key_extra')->nullable();
            $table->string('value');
            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });

        // Insert the records perfectly and then move on
        Artisan::call('db:seed', [
            '--class' => 'MeasuresParametersSeeder',
            '--force' => true
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measures_parameters');
    }
};
