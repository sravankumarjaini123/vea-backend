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
        if (Schema::hasTable('users_doubleoptins')) {
            Schema::dropIfExists('users_doubleoptins');
        }

        Schema::create('users_doubleoptins', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('base64_email');
            $table->string('code');
            $table->enum('status', ['created', 'confirmed', 'expired']);
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_doubleoptins', function (Blueprint $table) {
            //
        });
    }
};
