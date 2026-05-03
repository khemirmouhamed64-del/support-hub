<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketAttachmentsTable extends Migration
{
    public function up()
    {
        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ticket_id');
            $table->unsignedInteger('comment_id')->nullable();
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->enum('file_type', ['image', 'video', 'document']);
            $table->unsignedInteger('file_size')->nullable();
            $table->enum('source', ['client', 'dev'])->default('client');
            $table->timestamp('created_at')->nullable();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('comment_id')->references('id')->on('ticket_comments')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticket_attachments');
    }
}
