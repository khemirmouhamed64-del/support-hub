<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ticket_comments', function (Blueprint $table) {
            $table->enum('source', ['dev', 'client'])->default('dev')->after('visibility');
        });
    }

    public function down()
    {
        Schema::table('ticket_comments', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
