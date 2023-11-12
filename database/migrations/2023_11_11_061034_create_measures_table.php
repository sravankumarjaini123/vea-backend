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
        if (Schema::hasTable('measures')) {
            Schema::dropIfExists('measures');
        }

        Schema::create('measures', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['open', 'inProgress', 'complete'])->default('open');
            $table->text('name');
            $table->longText('description')->nullable();
            $table->unsignedBigInteger('measures_types_id')->nullable();
            $table->unsignedBigInteger('measures_processors_id')->nullable();
            $table->unsignedBigInteger('measures_categories_id')->nullable();
            $table->enum('implementation_time', ['immediate', 'medium', 'slow'])->nullable();

            $table->bigInteger('operating_life')->nullable();
            $table->bigInteger('investment_amount')->nullable();
            $table->year('investment_year')->nullable();
            $table->longText('investment_comments')->nullable();

            $table->longText('obstacles')->nullable();
            $table->longText('interactions')->nullable();
            $table->longText('additional_benefits')->nullable();
            $table->string('funding')->nullable();

            $table->unsignedBigInteger('industries_sectors_id')->nullable();
            $table->enum('company_size', ['KMU', 'large', 'any'])->nullable();
            $table->unsignedBigInteger('contacts_persons_id')->nullable();

            $table->json('source')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->foreign('measures_types_id')->references('id')->on('measures_types')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('measures_processors_id')->references('id')->on('measures_processors')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('measures_categories_id')->references('id')->on('measures_categories')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('industries_sectors_id')->references('id')->on('industries_sectors')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('contacts_persons_id')->references('id')->on('users')
                ->onDelete('set null')->onUpdate('cascade');
        });

        if (!Resources::where('slug', 'Measure')->exists()) {
            Resources::insert([
                'name' => 'Measure',
                'slug' => 'measure',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measures');
    }
};
