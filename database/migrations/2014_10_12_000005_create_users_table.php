<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::dropIfExists('users');
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salutations_id')->nullable();
            $table->unsignedBigInteger('titles_id')->nullable();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('username')->unique()->nullable();
            $table->boolean('sys_admin')->default(0)->nullable(false);
            $table->boolean('sys_customer')->default(0)->nullable(false);
            $table->rememberToken();
            $table->unsignedBigInteger('profile_photo_id')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->foreign('salutations_id')->references('id')->on('salutations')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('titles_id')->references('id')->on('titles')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('profile_photo_id')->references('id')->on('folders_files')
                ->onDelete('set null')->onUpdate('cascade');
        });

        // Insert the records perfectly and then move on
        Artisan::call('db:seed', [
            '--class' => 'UsersSeeder',
            '--force' => true
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
