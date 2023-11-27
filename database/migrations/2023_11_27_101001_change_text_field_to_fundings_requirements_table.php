<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Fundings;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        Schema::dropIfExists('fundings_requirements');
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        Schema::create('fundings_requirements', function (Blueprint $table) {
            $table->id();
            $table->longText('name');
            $table->unsignedBigInteger('display_order');

            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });

        Fundings::where('fundings_requirements_id', '!=', null)->update([
            'fundings_requirements_id' => null,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing TODO
    }
};
