<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketCommitsTable extends Migration
{
    public function up()
    {
        Schema::create('ticket_commits', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ticket_id');
            $table->string('hash', 40);
            $table->string('message', 255)->nullable();
            $table->string('url', 500)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticket_commits');
    }
}
