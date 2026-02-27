<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merchant_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->tinyInteger('status');
            $table->integer('payment_amount');
            $table->string('payment_currency', 3);
            $table->dateTime('status_updated_at')->nullable();
            $table->dateTime('payment_started_at')->nullable();
            $table->string('language', 30);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('merchant_orders');
    }
}
