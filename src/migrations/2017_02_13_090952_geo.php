<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Geo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('geo', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('parent_id')->nullable();
            $table->integer('left')->nullable();
            $table->integer('right')->nullable();
            $table->integer('depth')->default(0);
            $table->char('name', 60);
            $table->text('alternames');
            $table->char('country', 2);
            $table->string('a1code', 25);
            $table->char('level', 10);
            $table->bigInteger('population');
            $table->decimal('lat', 9, 6);
            $table->decimal('long', 9, 6);
            $table->char('timezone', 30);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('geo');
    }
}
