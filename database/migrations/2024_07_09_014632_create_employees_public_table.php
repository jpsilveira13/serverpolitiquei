<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees_public', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_type_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('matricula')->nullable();
            $table->boolean('active')->default(true);
            $table->string('nome')->nullable();
            $table->string('documento')->nullable();
            $table->date('admissao')->nullable();
            $table->string('cargo_funcao')->nullable();
            $table->string('lotacao')->nullable();
            $table->string('local_trabalho')->nullable();
            $table->integer('carga_horaria')->nullable();
            $table->foreign('employee_type_id')->references('id')->on('employee_types')->onDelete('set null');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees_public');
    }
};
