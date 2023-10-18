<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class CreateIndustriesSectorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('industries_sectors')) {
            Schema::dropIfExists('industries_sectors');
        }

        Schema::create('industries_sectors', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('industries_sectors_groups_id');
            $table->string('name', 255)->unique();

            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->foreign('industries_sectors_groups_id')->references('id')->on('industries_sectors_groups')
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
        Schema::dropIfExists('industries_sectors');
    }

} // End class
