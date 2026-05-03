<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddClientResponseTypeToHubNotifications extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE hub_notifications MODIFY COLUMN type ENUM('mention','assignment','status_change','new_ticket','comment','client_response') NOT NULL");
    }

    public function down()
    {
        DB::statement("ALTER TABLE hub_notifications MODIFY COLUMN type ENUM('mention','assignment','status_change','new_ticket','comment') NOT NULL");
    }
}
