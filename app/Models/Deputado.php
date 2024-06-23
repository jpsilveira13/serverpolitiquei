<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;

class Deputado extends Model
{
    use HasFactory;

    use Sluggable;


    protected $fillable = [
        'deputado_id',
        'nome',
        'sigla_uf',
        'id_legislatura',
        'url_foto',
        'email',
        'nome_civil',
        'cpf',
        'sexo',
        'data_nascimento',
        'uf_nascimento',
        'municipio_nascimento',
        'escolaridade',
        'gabinete_nome',
        'gabinete_predio',
        'gabinete_sala',
        'gabinete_andar',
        'gabinete_telefone',
        'sigla_partido',
        'rede_social',
        'partido_id'
    ];

    protected $casts = [
        'rede_social' => 'array',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'nome'
            ]
        ];
    }

     public function partido()
    {
        return $this->belongsTo(Partido::class);
    }

    public function despesas()
    {
        return $this->hasMany(Despesa::class);
    }
}
