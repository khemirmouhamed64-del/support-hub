<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketPrLinksTable extends Migration
{
    public function up()
    {
        Schema::create('ticket_pr_links', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ticket_id');
            $table->string('url', 500);
            $table->string('title', 255)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticket_pr_links');
    }
}
