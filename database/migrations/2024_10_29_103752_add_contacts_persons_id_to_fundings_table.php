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
        if (!Schema::hasColumn('fundings', 'contacts_persons_id')) {
            Schema::table('fundings', function (Blueprint $table) {
                $table->unsignedBigInteger('contacts_persons_id')->nullable()->after('is_active');

                $table->foreign('contacts_persons_id')->references('id')->on('users')
                    ->onDelete('set null')->onUpdate('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('fundings', 'contacts_persons_id')) {
            Schema::table('fundings', function (Blueprint $table) {
                $table->dropColumn('contacts_persons_id');
            });
        }
    }
};
