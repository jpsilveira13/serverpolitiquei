<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeputadosTable extends Migration
{
    public function up()
    {
        Schema::create('deputados', function (Blueprint $table) {
            $table->id();
            $table->integer('deputado_id')->unique();
            $table->string('nome');
            $table->string('sigla_uf');
            $table->integer('id_legislatura');
            $table->string('url_foto');
            $table->string('email')->nullable();
            $table->string('nome_civil')->nullable();
            $table->string('cpf')->nullable();
            $table->string('sexo')->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('uf_nascimento')->nullable();
            $table->string('municipio_nascimento')->nullable();
            $table->string('escolaridade')->nullable();
            $table->string('gabinete_nome')->nullable();
            $table->string('gabinete_predio')->nullable();
            $table->string('gabinete_sala')->nullable();
            $table->string('gabinete_andar')->nullable();
            $table->string('gabinete_telefone')->nullable();
            $table->json('rede_social')->nullable();
            $table->unsignedBigInteger('partido_id')->nullable();
            $table->timestamps();

            $table->foreign('partido_id')->references('id')->on('partidos')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('deputados');
    }
}


