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
        if (Schema::hasTable('users_roles')) {
            Schema::dropIfExists('users_roles');
        }

        Schema::create('users_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('users_id');
            $table->unsignedBigInteger('roles_id');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            //FOREIGN KEY
            $table->foreign('users_id')->references('id')->on('users')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('roles_id')->references('id')->on('roles')
                ->onDelete('cascade')->onUpdate('cascade');

            //PRIMARY KEYS
            $table->primary(['users_id','roles_id']);
        });

        // Insert the records perfectly and then move on
        Artisan::call('db:seed', [
            '--class' => 'RolesSeeder',
            '--force' => true
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_roles');
    }
};
