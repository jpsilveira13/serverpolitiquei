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
        Schema::create('partidos', function (Blueprint $table) {
            $table->id();
            $table->integer('id_partido');
            $table->string('sigla');
            $table->string('nome');
            $table->string('situacao')->nullable();
            $table->integer('total_posse')->nullable();
            $table->integer('total_membros')->nullable();
            $table->string('lider_nome')->nullable();
            $table->string('lider_uri')->nullable();
            $table->string('lider_uf')->nullable();
            $table->string('url_logo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partidos');
    }
};
