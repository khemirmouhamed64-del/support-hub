<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddTaskToTicketTypeEnum extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE tickets MODIFY COLUMN ticket_type ENUM('bug', 'configuration', 'question', 'feature_request', 'task') NOT NULL");
    }

    public function down()
    {
        // Remove 'task' — existing task tickets will cause an error if any exist
        DB::statement("ALTER TABLE tickets MODIFY COLUMN ticket_type ENUM('bug', 'configuration', 'question', 'feature_request') NOT NULL");
    }
}
