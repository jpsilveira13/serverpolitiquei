<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Partido;
use App\Models\Deputado;
use Illuminate\Support\Facades\DB;


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


    public function getPartidosScreen()
    {
        $partidos = Partido::withCount(['deputados as gasto_total' => function ($query) {
            $query->select(DB::raw('SUM(despesas.valor_liquido)'))
                ->join('despesas', 'deputados.id', '=', 'despesas.deputado_id');
        }])
        ->orderBy('gasto_total', 'desc')
        ->get();
        $liderNomes = $partidos->pluck('lider_nome')->unique();
        $lideres = Deputado::whereIn('nome', $liderNomes)->get()->keyBy('nome');

        foreach ($partidos as $partido) {
            $partido->lider_url_foto = $lideres[$partido->lider_nome]->url_foto ?? null;
        }
        return response()->json($partidos);
    }

    public function listPartidos()
    {
        $partidos = Partido::orderBy('nome', 'asc')->get();

        return response()->json($partidos);
    }

}
