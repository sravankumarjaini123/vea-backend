<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Resources;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('external_videos')) {
            if (!Schema::hasColumn('external_videos', 'type')) {
                Schema::table('external_videos', function (Blueprint $table) {
                    $table->string('type')->default('video')->after('id');
                    $table->unsignedBigInteger('file_id')->nullable()->after('is_active');

                    $table->foreign('file_id')->references('id')->on('folders_files')
                        ->onDelete('cascade')->onUpdate('cascade');
                });
            }
        }

        if (Schema::hasTable('external_videos')) {
            Schema::rename('external_videos', 'archives');
        }

        if (!Resources::where('slug', 'archives')->exists()) {
            DB::table('resources')->where('slug', 'external-videos')->update([
                'name' => 'Archives',
                'slug' => 'archives'
            ]);
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_videos', function (Blueprint $table) {
            //
        });
    }
};
