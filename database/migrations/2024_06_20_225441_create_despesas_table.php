<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDespesasTable extends Migration
{
    public function up()
    {
        Schema::create('despesas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deputado_id');
            $table->integer('documento_id')->unique();
            $table->integer('ano');
            $table->integer('mes');
            $table->string('tipo_despesa');
            $table->string('cnpj_cpf_fornecedor');
            $table->string('fornecedor');
            $table->decimal('valor_documento', 15, 2);
            $table->decimal('valor_liquido', 15, 2);
            $table->date('data_emissao');
            $table->string('url_documento')->nullable();
            $table->foreign('deputado_id')->references('id')->on('deputados')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('despesas');
    }
}
