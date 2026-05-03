<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketStatusHistoryTable extends Migration
{
    public function up()
    {
        Schema::create('ticket_status_history', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ticket_id');
            $table->string('old_column', 50)->nullable();
            $table->string('new_column', 50);
            $table->unsignedInteger('changed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('changed_by')->references('id')->on('team_members');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticket_status_history');
    }
}
