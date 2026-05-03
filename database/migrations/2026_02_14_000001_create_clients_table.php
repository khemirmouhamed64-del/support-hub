<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientsTable extends Migration
{
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->increments('id');
            $table->string('client_identifier', 100)->unique();
            $table->string('business_name', 255);
            $table->string('api_callback_url', 500)->nullable();
            $table->string('api_key', 255);
            $table->enum('priority_level', ['low', 'medium', 'high', 'vip'])->default('medium');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('clients');
    }
}
