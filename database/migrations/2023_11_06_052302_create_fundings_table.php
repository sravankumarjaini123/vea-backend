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
        if (Schema::hasTable('fundings')) {
            Schema::dropIfExists('fundings');
        }

        Schema::create('fundings', function (Blueprint $table) {
            $table->id();
            $table->mediumText('programme')->nullable();
            $table->longText('details')->nullable();
            $table->unsignedBigInteger('fundings_requirements_id')->nullable();
            $table->unsignedBigInteger('fundings_types_id')->nullable();
            $table->unsignedBigInteger('fundings_bodies_id')->nullable();
            $table->longText('head')->nullable();
            $table->text('deadline')->nullable();
            $table->date('period')->nullable();
            $table->json('source')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->foreign('fundings_requirements_id')->references('id')->on('fundings_requirements')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('fundings_types_id')->references('id')->on('fundings_types')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('fundings_bodies_id')->references('id')->on('fundings_bodies')
                ->onDelete('set null')->onUpdate('cascade');
        });

        if (!Resources::where('slug', 'funding')->exists()) {
            Resources::insert([
                'name' => 'Funding',
                'slug' => 'funding',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fundings');
    }
};
