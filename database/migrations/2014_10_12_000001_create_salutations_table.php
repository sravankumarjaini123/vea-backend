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
        if (Schema::hasTable('salutations')) {
            Schema::dropIfExists('salutations');
        }

        Schema::create('salutations', function (Blueprint $table) {
            $table->id();
            $table->string('salutation', 255)->unique();
            $table->integer('display_order');
            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });

        // Insert the records perfectly and then move on
        Artisan::call('db:seed', [
            '--class' => 'SalutationsSeeder',
            '--force' => true
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salutations');
    }
};
