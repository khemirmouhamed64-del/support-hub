<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddSpFileSourceToTicketAttachments extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE ticket_attachments MODIFY source ENUM('client','dev','sp_file') DEFAULT 'client'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE ticket_attachments MODIFY source ENUM('client','dev') DEFAULT 'client'");
    }
}
