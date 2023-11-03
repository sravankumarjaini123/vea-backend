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
        if (Schema::hasTable('fundings_states')) {
            Schema::dropIfExists('fundings_states');
        }

        Schema::create('fundings_states', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedBigInteger('display_order');

            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });
        if (!Resources::where('slug', 'funding-masterdata')->exists()) {
            Resources::insert([
                'name' => 'Funding MasterData',
                'slug' => 'funding-masterdata',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fundings_states');
    }
};
