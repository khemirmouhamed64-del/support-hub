<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddErpResponseIdToTicketComments extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE ticket_comments ADD COLUMN erp_response_id INT UNSIGNED NULL AFTER source');
    }

    public function down()
    {
        DB::statement('ALTER TABLE ticket_comments DROP COLUMN erp_response_id');
    }
}
