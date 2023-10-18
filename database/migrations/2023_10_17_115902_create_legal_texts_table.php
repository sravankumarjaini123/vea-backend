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
        if(Schema::hasTable('legal_texts')) {
            Schema::dropIfExists('legal_texts');
        }

        Schema::create('legal_texts', function (Blueprint $table) {
            $table->id();
            $table->integer('version_id');
            $table->string('name',255);
            $table->string('title',255);
            $table->longText('description')->nullable();
            $table->mediumText('element')->nullable();
            $table->boolean('is_published')->default(0);
            $table->boolean('is_active')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });

        DB::table('resources')->insert([
            'name' => 'Legal Texts',
            'slug' => 'legal-texts',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_texts');
    }
};
