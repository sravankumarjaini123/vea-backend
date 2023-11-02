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
        Schema::table('wordpress_posts', function (Blueprint $table) {
            $table->enum('sync_status',['synced','unSynced','processing','lastSyncFailed'])
                ->after('wp_post_id')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wordpress_posts', function (Blueprint $table) {
            $table->dropColumn('sync_status');
        });
    }
};
