<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Despesa extends Model
{
    use HasFactory;

    protected $fillable = [
        'deputado_id',
        'documento_id',
        'ano',
        'mes',
        'tipo_despesa',
        'cnpj_cpf_fornecedor',
        'fornecedor',
        'valor_documento',
        'valor_liquido',
        'data_emissao',
        'url_documento'
    ];

    public function deputado()
    {
        return $this->belongsTo(Deputado::class);
    }
}
