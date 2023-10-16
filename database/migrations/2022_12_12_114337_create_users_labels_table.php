<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersLabelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( Schema::hasTable('users_labels')) {
            Schema::dropIfExists('users_labels');
        }

        Schema::create('users_labels', function (Blueprint $table) {
            $table->unsignedBigInteger('labels_id')->unsigned();
            $table->unsignedBigInteger('users_id');

            $table->timestamps();

            $table->foreign('labels_id', 'fk_users_labels_labels_id')
                ->references('id')->on('labels')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('users_id', 'fk_users_labels_users_id')
                ->references('id')->on('users')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });

        if (! Schema::hasColumn('users', 'is_blocked')) {
            Schema::table('users', function (Blueprint $table){
                 $table->boolean('is_blocked')->after('sys_customer')->default(0);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users_labels');
        if (Schema::hasColumn('users', 'is_blocked')) {
            Schema::table('users', function (Blueprint $table){
                $table->dropColumn('is_blocked');
            });
        }
    }
}
