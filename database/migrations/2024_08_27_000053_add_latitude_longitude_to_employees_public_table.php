<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLatitudeLongitudeToEmployeesPublicTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees_public', function (Blueprint $table) {
            $table->decimal('latitude', 10, 6)->nullable()->after('local_trabalho');
            $table->decimal('longitude', 10, 6)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees_public', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
}
