<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MakeClientIdNullableOnTickets extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE tickets MODIFY client_id INT UNSIGNED NULL');
    }

    public function down()
    {
        DB::statement('ALTER TABLE tickets MODIFY client_id INT UNSIGNED NOT NULL');
    }
}
