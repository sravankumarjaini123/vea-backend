<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class CreatePartnersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('partners')) {
            Schema::dropIfExists('partners');
        }

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->unsignedBigInteger('logo_rectangle_file_id')->nullable();
            $table->unsignedBigInteger('logo_square_file_id')->nullable();
            $table->string('name')->unique();
            $table->string('street')->nullable();
            $table->string('street_extra')->nullable();
            $table->integer('zip_code')->nullable();
            $table->string('city')->nullable();
            $table->unsignedBigInteger('countries_id')->nullable()->unsigned();
            $table->integer('created_by')->nullable();
            // Finance Information
            $table->string('tax_number')->nullable();
            $table->string('ust_id')->nullable();
            $table->string('debtor_number')->nullable();
            $table->string('creditor_number')->nullable();
            $table->boolean('invoices_seperated')->default(1);
            // Payment
            $table->string('billing_send_email')->nullable();
            $table->string('payment_target_days')->nullable();
            $table->longText('notes')->nullable();

            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->foreign('logo_rectangle_file_id')->references('id')->on('folders_files')
                ->onDelete('set null')->onUpdate('cascade');

            $table->foreign('logo_square_file_id')->references('id')->on('folders_files')
                ->onDelete('set null')->onUpdate('cascade');

            $table->foreign('countries_id')->references('id')->on('countries')
                ->onDelete('set null')->onUpdate('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('partners');
    }
}
