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
        if (!Schema::hasColumn('partners', 'email')) {
            Schema::table('partners', function (Blueprint $table) {
                $table->string('email')->nullable()->after('city');
                $table->string('website')->nullable()->after('email');
                $table->string('telephone')->nullable()->after('website');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('partners', 'email')) {
            Schema::table('partners', function (Blueprint $table) {
                $table->dropColumn(['email', 'website', 'telephone']);
            });
        }
    }
};
