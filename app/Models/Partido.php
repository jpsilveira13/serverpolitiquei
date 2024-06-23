<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partido extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'id_partido',
        'sigla',
        'nome',
        'situacao',
        'total_posse',
        'total_membros',
        'lider_nome',
        'lider_uri',
        'lider_uf',
        'url_logo'
    ];

    public function deputados()
    {
        return $this->hasMany(Deputado::class, 'partido_id', 'id');
    }
}
