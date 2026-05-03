<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHubNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('hub_notifications', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('recipient_id');
            $table->unsignedInteger('ticket_id')->nullable();
            $table->enum('type', ['mention', 'assignment', 'status_change', 'new_ticket', 'comment']);
            $table->string('title', 255);
            $table->text('message')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('recipient_id')->references('id')->on('team_members');
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('hub_notifications');
    }
}
