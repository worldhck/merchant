<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SetObjectNullableInOrderLines extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('merchant_order_lines', function (Blueprint $table) {
            $table->unsignedInteger('object_id')->nullable()->change();
            $table->string('object_class', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('merchant_order_lines', function (Blueprint $table) {
            $table->unsignedInteger('object_id')->nullable(false)->change();
            $table->string('object_class', 255)->nullable(false)->change();
        });
    }
}
