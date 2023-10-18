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
        if (!Schema::hasColumn('emails_settings', 'subject')) {
            Schema::table('emails_settings', function (Blueprint $table) {
                $table->text('subject')->after('display_name')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('emails_settings', 'subject')) {
            Schema::table('emails_settings', function (Blueprint $table) {
                $table->dropColumn(['subject']);
            });
        }
    }
};
