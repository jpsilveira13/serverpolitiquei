<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('deputados', function (Blueprint $table) {
            $table->string('slug')->unique()->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('deputados', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
