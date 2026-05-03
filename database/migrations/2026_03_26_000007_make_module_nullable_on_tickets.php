<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MakeModuleNullableOnTickets extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE tickets MODIFY module VARCHAR(100) NULL");
    }

    public function down()
    {
        DB::statement("ALTER TABLE tickets MODIFY module VARCHAR(100) NOT NULL");
    }
}
