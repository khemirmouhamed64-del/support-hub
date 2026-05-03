<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketMentionsTable extends Migration
{
    public function up()
    {
        Schema::create('ticket_mentions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('comment_id');
            $table->unsignedInteger('mentioned_member_id');
            $table->boolean('is_read')->default(false);
            $table->boolean('notified_by_email')->default(false);
            $table->timestamp('created_at')->nullable();

            $table->foreign('comment_id')->references('id')->on('ticket_comments')->onDelete('cascade');
            $table->foreign('mentioned_member_id')->references('id')->on('team_members');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticket_mentions');
    }
}
