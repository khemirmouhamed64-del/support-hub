<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModuleExpertiseTable extends Migration
{
    public function up()
    {
        Schema::create('module_expertise', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('team_member_id');
            $table->string('module_name', 100);
            $table->boolean('is_primary')->default(false);

            $table->foreign('team_member_id')->references('id')->on('team_members')->onDelete('cascade');
            $table->unique(['team_member_id', 'module_name'], 'unique_member_module');
        });
    }

    public function down()
    {
        Schema::dropIfExists('module_expertise');
    }
}
