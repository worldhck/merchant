<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merchant_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('object_class', 255);
            $table->integer('object_id');
            $table->tinyInteger('status');
            $table->string('gateway', 255);
            $table->text('options');
            $table->integer('amount');
            $table->string('token_id', 70);
            $table->string('token_reference', 150)->nullable();
            $table->text('description')->nullable();
            $table->text('error')->nullable();
            $table->text('response')->nullable();
            $table->string('language_code');
            $table->string('currency_code');
            $table->timestamps();

            // add indexes
            $table->unique('token_id'); // unique per application
            $table->index('object_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('merchant_transactions');
    }
}
