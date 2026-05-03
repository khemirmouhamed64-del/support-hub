<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsTable extends Migration
{
    public function up()
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('client_id');
            $table->unsignedInteger('external_ticket_id')->nullable();
            $table->string('ticket_number', 20)->unique();

            // Reporter info
            $table->string('reporter_name', 255);
            $table->string('reporter_email', 255)->nullable();

            // Classification
            $table->enum('ticket_type', ['bug', 'configuration', 'question', 'feature_request']);
            $table->string('module', 100);
            $table->string('sub_module', 100)->nullable();

            // Priorities
            $table->enum('issue_priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('client_priority', ['low', 'medium', 'high', 'vip'])->default('medium');
            $table->unsignedTinyInteger('effective_priority')->default(5);

            // Content
            $table->string('subject', 255);
            $table->text('description');
            $table->text('steps_to_reproduce')->nullable();
            $table->text('expected_behavior')->nullable();
            $table->string('browser_url', 500)->nullable();

            // Kanban
            $table->enum('board_column', [
                'to_do', 'in_progress', 'blocked', 'code_review',
                'qa_testing', 'ready_for_release', 'done'
            ])->default('to_do');
            $table->unsignedInteger('assigned_to')->nullable();

            // Dev notes
            $table->string('pr_link', 500)->nullable();
            $table->string('commit_info', 500)->nullable();
            $table->text('sp_notes')->nullable();
            $table->text('deploy_notes')->nullable();

            // Resolution
            $table->text('resolution_message')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('archived_at')->nullable();

            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients');
            $table->foreign('assigned_to')->references('id')->on('team_members');
            $table->index('board_column');
            $table->index('effective_priority');
            $table->index('client_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tickets');
    }
}
