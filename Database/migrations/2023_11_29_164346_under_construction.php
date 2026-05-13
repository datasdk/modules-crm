<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UnderConstruction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable("under_construction"))
        Schema::create('under_construction', function (Blueprint $table) {

            $table->integer('id');
            $table->tinyInteger('under_construction',1);
            $table->string('access_key',250);
            $table->string('title',250);
            $table->text('description');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if(Schema::hasTable('under_construction'))
        Schema::dropIfExists('under_construction');
    }
}

