<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Partido;
use App\Models\Deputado;



class PartidoController extends Controller
{

    public function index(){

        $partidos = Partido::orderBy('nome', 'asc')->get();
        $liderNomes = $partidos->pluck('lider_nome')->unique();
        $lideres = Deputado::whereIn('nome', $liderNomes)->get()->keyBy('nome');
        foreach ($partidos as $partido) {
            $partido->lider_url_foto = $lideres[$partido->lider_nome]->url_foto ?? null;
        }
        return response()->json($partidos);
    }

}
