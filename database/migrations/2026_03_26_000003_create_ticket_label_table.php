<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketLabelTable extends Migration
{
    public function up()
    {
        Schema::create('ticket_label', function (Blueprint $table) {
            $table->unsignedInteger('ticket_id');
            $table->unsignedInteger('label_id');

            $table->primary(['ticket_id', 'label_id']);
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('label_id')->references('id')->on('labels')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticket_label');
    }
}
