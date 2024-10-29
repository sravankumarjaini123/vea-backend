<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Resources;
use App\Models\Partners;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('external_videos');
        Schema::create('external_videos', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->longText('description')->nullable();
            $table->longText('url')->nullable();
            $table->string('source')->nullable();
            $table->boolean('is_active')->default(1);
            $table->dateTime('date_added')->nullable();
            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });

        // Add the Resource to the System
        if (!Resources::where('slug', 'external-videos')->exists()) {
            Resources::insert([
                'id' => 22,
                'name' => 'External Videos',
                'slug' => 'external-videos',
            ]);
        }
        // Add the Resource for all the Partners
        $partners = Partners::all();
        foreach ($partners as $partner) {
            $partner_update = Partners::where('id', $partner->id)->first();
            $partner_update->resources()->attach(22, ['created_at'=>Carbon::now()->format('Y-m-d H:i:s')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_videos');
    }
};
