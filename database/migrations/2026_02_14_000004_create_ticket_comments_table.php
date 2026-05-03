<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketCommentsTable extends Migration
{
    public function up()
    {
        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ticket_id');
            $table->unsignedInteger('author_id')->nullable();
            $table->string('author_name', 255);
            $table->enum('visibility', ['internal', 'client'])->default('internal');
            $table->text('content');
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('author_id')->references('id')->on('team_members');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticket_comments');
    }
}
