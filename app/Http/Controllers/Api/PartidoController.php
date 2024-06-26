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
    $partidos = Partido::with(['deputados' => function ($query) {
            $query->with(['despesas' => function ($subQuery) {
                    $subQuery->select('deputado_id', DB::raw('SUM(valor_liquido) as total_despesas'))
                        ->groupBy('deputado_id');
                }])
                ->orderByDesc('total_despesas') // Ordena os deputados por despesas decrescente
                ->take(3); // Pega apenas os 3 deputados com maiores despesas
        }])
        ->get()
        ->map(function ($partido) {
            $partido->gasto_total = $partido->deputados->sum('total_despesas');
            return $partido;
        });

    return response()->json($partidos);
}




}
