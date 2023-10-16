<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartnersIndustriesSectorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('partners_industries_sectors', function (Blueprint $table) {

            $table->unsignedBigInteger('industries_sectors_id');
            $table->unsignedBigInteger('partners_id');

            $table->timestamps();
            //$table->softDeletes();

            $table->foreign('industries_sectors_id', 'fk_industries_sectors_id')
                ->references('id')->on('industries_sectors')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('partners_id', 'fk_partners_id')
                ->references('id')->on('partners')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('partners_industries_sectors');
    }

} // End function
