<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersLoginTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('users_login')) {
            Schema::dropIfExists('users_login');
        }

        Schema::create('users_login', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('users_id');
            $table->dateTime('date')->nullable();
            $table->string('ip')->nullable();
            $table->string('browser_agent')->nullable();
            $table->string('status');
            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->foreign('users_id')->references('id')->on('users')
                ->onDelete('cascade')->onUpdate('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users_login');
    }
}
