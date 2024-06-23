<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Despesa; // Importe o modelo Despesa aqui
use App\Models\Deputado; // Importe o modelo Despesa aqui
use GuzzleHttp\Client;


class DeputadoController extends Controller
{

    public function index(Request $request)
    {

        $query = Deputado::query()->inRandomOrder();


        $deputadosIds = $query->pluck('id')->toArray();


        $valorTotal2023 = $this->calcularValorTotal($deputadosIds, 2023);
        $valorTotal2024 = $this->calcularValorTotal($deputadosIds, 2024);


        $deputados = $query->paginate($request->input('per_page', 12));


        $deputados->each(function ($deputado) use ($valorTotal2023, $valorTotal2024) {
            $deputado->valor_total_2023 = $valorTotal2023[$deputado->id] ?? 0;
            $deputado->valor_total_2024 = $valorTotal2024[$deputado->id] ?? 0;
            $deputado->valor_total_2023_2024 = $deputado->valor_total_2023 + $deputado->valor_total_2024;
        });

        return response()->json($deputados);
    }

    private function calcularValorTotal(array $deputadosIds, int $ano)
    {
        return Despesa::select('deputado_id', \DB::raw('SUM(valor_documento) as total_gasto'))
            ->whereIn('deputado_id', $deputadosIds)
            ->where('ano', $ano)
            ->groupBy('deputado_id')
            ->pluck('total_gasto', 'deputado_id')
            ->toArray();
    }

    public function deputado(Request $request, $slug)
    {
        try {

            $deputado = Deputado::where('slug', $slug)->with('partido')->firstOrFail();
            $totalGastos = Despesa::where('deputado_id', $deputado->id)->sum('valor_liquido');
            $deputado->total_gastos = $totalGastos;

            return response()->json($deputado);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Deputado não encontrado'], 404);
        }

    }
    public function deputadosAleatorios(Request $request)
    {
        // Busca 5 deputados aleatórios
        $deputados = Deputado::inRandomOrder()->limit(6)->with('partido')->get();

        // Para cada deputado, calcular o total de gastos
        $deputados->each(function ($deputado) {
            $totalGastos = Despesa::where('deputado_id', $deputado->id)->sum('valor_liquido');
            $deputado->total_gastos = $totalGastos;
        });

        return response()->json($deputados);
    }

    public function deputadoDespesa(Request $request, $deputado_id)
    {

        $page = $request->query('page', 1);
        $despesas = Despesa::where('deputado_id', $deputado_id)
                            ->orderBy('valor_liquido', 'desc')
                            ->paginate(5, ['*'], 'page', $page);

        return response()->json($despesas);
    }

//     public function atualizarSlugs()
// {
//     $deputados = Deputado::all();

//     foreach ($deputados as $deputado) {
//         $deputado->slug = \Illuminate\Support\Str::slug($deputado->nome);
//         $deputado->save();
//     }

//     return response()->json(['message' => 'Slugs atualizados com sucesso']);
// }

    public function rankingGastadores() {

        $topGastadores = Despesa::select('deputado_id', \DB::raw('SUM(valor_documento) as total_gastos'))
            ->groupBy('deputado_id')
            ->orderBy('total_gastos', 'desc')
            ->take(10)
            ->get();

        $deputados = Deputado::with('partido') // Carregar a relação partido junto
            ->whereIn('id', $topGastadores->pluck('deputado_id'))
            ->get()
            ->keyBy('id');

        $ranking = $topGastadores->map(function ($despesa) use ($deputados) {
            $deputado = $deputados[$despesa->deputado_id];

            $urlFoto = str_replace('\\', '', $deputado->url_foto);

            return [
                'deputado' => [
                    'id' => $deputado->id,
                    'nome' => $deputado->nome,
                    'sigla_partido' => $deputado->sigla_partido ?? 'N/A',
                    'url_foto' => $urlFoto, // URL da foto corrigida
                    'url_logo' => $deputado->partido->url_logo ?? 'N/A', // URL do logo do partido
                ],
                'total_gastos' => $despesa->total_gastos,
            ];
        })->sortByDesc('total_gastos')->values();

        return response()->json($ranking);
    }


    public function mandatosExternos(Request $request, $deputado_id){
        $client = new Client();
        $url = "https://dadosabertos.camara.leg.br/api/v2/deputados/{$deputado_id}/mandatosExternos";

        try {
            $response = $client->get($url);
            $data = json_decode($response->getBody()->getContents(), true);

            return response()->json($data, 200);
        } catch (RequestException $e) {
            return response()->json(['error' => 'Erro ao buscar dados do deputado'], 500);
        }
    }

    public function eventos(Request $request, $deputado_id){
        $client = new Client();
        $url = "https://dadosabertos.camara.leg.br/api/v2/deputados/{$deputado_id}/eventos";

        try {
            $response = $client->get($url);
            $data = json_decode($response->getBody()->getContents(), true);

            return response()->json($data, 200);
        } catch (RequestException $e) {
            return response()->json(['error' => 'Erro ao buscar dados do deputado'], 500);
        }
    }

    public function orgaos(Request $request, $deputado_id){
        $client = new Client();
        $url = "https://dadosabertos.camara.leg.br/api/v2/deputados/{$deputado_id}/orgaos";

        try {
            $response = $client->get($url);
            $data = json_decode($response->getBody()->getContents(), true);

            return response()->json($data, 200);
        } catch (RequestException $e) {
            return response()->json(['error' => 'Erro ao buscar dados do deputado'], 500);
        }
    }

}
