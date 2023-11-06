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
        if (Schema::hasTable('fundings_fundings_eligibilities')) {
            Schema::dropIfExists('fundings_fundings_eligibilities');
        }

        Schema::create('fundings_fundings_eligibilities', function (Blueprint $table) {
            $table->unsignedBigInteger('fundings_id');
            $table->unsignedBigInteger('fundings_eligibilities_id');

            $table->foreign('fundings_id')->references('id')->on('fundings')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('fundings_eligibilities_id', 'fundings_eligibilities_id_foreign')->references('id')->on('fundings_eligibilities')
                ->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('fundings_fundings_eligibilities');
    }
};
