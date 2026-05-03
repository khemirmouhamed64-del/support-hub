<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLabelsTable extends Migration
{
    public function up()
    {
        Schema::create('labels', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50);
            $table->string('color', 7)->default('#6c757d');
            $table->timestamps();

            $table->unique('name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('labels');
    }
}
