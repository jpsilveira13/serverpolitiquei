<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicEmployee extends Model
{
    protected $fillable = [
        'matricula',
        'nome',
        'documento',
        'admissao',
        'cargo_funcao',
        'lotacao',
        'local_trabalho',
        'carga_horaria',
    ];

    // Relacionamento com os pagamentos
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
