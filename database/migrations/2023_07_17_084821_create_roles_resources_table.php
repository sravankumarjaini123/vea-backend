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
        if (Schema::hasTable('roles_resources')) {
            Schema::dropIfExists('roles_resources');
        }

        Schema::create('roles_resources', function (Blueprint $table) {
            $table->unsignedBigInteger('roles_id');
            $table->unsignedBigInteger('resources_id');
            $table->unsignedBigInteger('permissions_id');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->foreign('roles_id')->references('id')->on('roles')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('resources_id')->references('id')->on('resources')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('permissions_id')->references('id')->on('permissions')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->primary(['roles_id','resources_id','permissions_id'], 'pk_roles_resources');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles_resources');
    }
};
