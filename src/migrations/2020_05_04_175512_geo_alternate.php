<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Geoalternate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('geoalternate', function (Blueprint $table) {
            $table->unsignedInteger('alternateNameId');
            $table->unsignedInteger('geonameid')->references('id')->on('geo');
            $table->char('isolanguage', 7);
            $table->text('alternatename');
            $table->boolean('isPreferredName')->default(false);
            $table->boolean('isShortName')->default(false);
            $table->boolean('isColloquial')->default(false);
            $table->boolean('isHistoric')->default(false);
            $table->char('from', 20)->nullable();
            $table->char('to', 20)->nullable();
            $table->primary('alternateNameId');
            $table->index('geonameid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('geoalternate');
    }
}
