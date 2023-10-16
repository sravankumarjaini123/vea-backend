<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersForgotPasswordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('users_forgot_passwords')) {
            Schema::dropIfExists('users_forgot_passwords');
        }

        Schema::create('users_forgot_passwords', function (Blueprint $table) {
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
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users_forgot_passwords');
    }
}
