<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('measures', 'implementation_time')) {
            DB::statement("ALTER TABLE `measures` MODIFY `implementation_time` ENUM('immediate', 'medium', 'slow', 'longTerm') NULL;");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('measures', 'implementation_time')) {
            DB::statement("ALTER TABLE `measures` MODIFY `implementation_time` ENUM('immediate', 'medium', 'slow') NULL;");
        }
    }
};
