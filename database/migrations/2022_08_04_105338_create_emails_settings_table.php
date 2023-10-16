<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class CreateEmailsSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('emails_settings')) {
            Schema::dropIfExists('emails_settings');
        }

        Schema::create('emails_settings', function (Blueprint $table) {
            $table->id();
            $table->string('technologies');
            $table->string('name');
            $table->text('display_name');
            $table->unsignedBigInteger('emails_id')->nullable();
            $table->unsignedBigInteger('emails_templates_id')->nullable();
            $table->timestamps();

            $table->foreign('emails_id')->references('id')->on('emails')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('emails_templates_id')->references('id')->on('emails_templates')
                ->onDelete('set null')->onUpdate('cascade');
        });

        // Insert the data for local or production Servers
        Artisan::call('db:seed', [
            '--class' => 'EmailsSettingsSeeder',
            '--force' => true
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('emails_settings');
    }
}
