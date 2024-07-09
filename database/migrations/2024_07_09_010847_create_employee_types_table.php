<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeTypesTable extends Migration
{
    public function up()
    {
        Schema::create('employee_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Inserir os tipos de funcionários
        $employeeTypes = [
            ['name' => 'EFETIVO'],
            ['name' => 'COMISSIONADO'],
            ['name' => 'AGENTE_POLITIVO'],
            ['name' => 'CONTRATADO'],
            ['name' => 'ESTAGIARIO'],
            ['name' => 'ESTAVEL'],
            ['name' => 'FUNCAO_PUBLICA'],
            ['name' => 'CONCURSADO'],
        ];

        DB::table('employee_types')->insert($employeeTypes);
    }

    public function down()
    {
        Schema::dropIfExists('employee_types');
    }
}
