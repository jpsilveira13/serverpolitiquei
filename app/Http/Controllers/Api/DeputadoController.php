<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

use App\Models\Despesa;
use App\Models\Deputado;


class DeputadoController extends Controller
{

    public function index(Request $request)
    {
        try {
            $query = Deputado::query();
            $query->inRandomOrder();

            $perPage = $request->input('per_page', 8);
            $deputados = $query->paginate($perPage);

            // Pegar os IDs dos deputados paginados
            $deputadosIds = $deputados->pluck('id')->toArray();

            // Calcular o valor total gasto para os deputados paginados
            $valorTotal2023_2024 = $this->calcularValorTotal($deputadosIds);

            // Atribuir o valor total 2023-2024 para cada deputado
            $deputados->each(function ($deputado) use ($valorTotal2023_2024) {
                // Verificar se o ID do deputado está presente no array calculado
                if (isset($valorTotal2023_2024[$deputado->id])) {
                    $deputado->valor_total = $valorTotal2023_2024[$deputado->id];
                } else {
                    $deputado->valor_total = 0; // Valor padrão se não houver correspondência
                }
            });

            return response()->json($deputados);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao buscar deputados: ' . $e->getMessage()], 500);
        }
    }


    public function indexSearch(Request $request)
    {
        try {
            $query = Deputado::query();

            // Aplicar filtro por nome
            if ($request->has('name')) {
                $query->where('nome', 'like', '%' . $request->input('name') . '%');
            }

            if ($request->has('party_id')) {
                $query->where('sigla_partido', 'like', '%' . $request->input('party_id') . '%');
            }

            $perPage = $request->input('per_page', 8);

           $maxExpense = (float) $request->input('max_expense');
            if($maxExpense > 0.0){
                $query->withSum('despesas', 'valor_liquido')
                ->when($maxExpense > 0, function ($query) use ($maxExpense) {
                    $query->whereHas('despesas', function ($q) use ($maxExpense) {
                        $q->selectRaw('sum(valor_liquido) as total_despesas')
                            ->having('total_despesas', '<=', $maxExpense);
                    });
                })
                ->orderByDesc('despesas_sum_valor_liquido');

            }else{
                $sortBy = $request->input('sort_by', 'nome'); // Coloque o padrão desejado aqui
                $sortOrder = $request->input('sort_order', 'asc');
                $query->orderBy($sortBy, $sortOrder);
            }

            $deputados = $query->paginate($perPage);
            $deputados->each(function ($deputado) {
                $deputado->valor_total = $this->calcularValorTotal([$deputado->id])[$deputado->id] ?? 0;

            });


            return response()->json($deputados);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao buscar deputados: ' . $e->getMessage()], 500);
        }
    }


    private function calcularValorTotal(array $deputadosIds)
    {
        return Despesa::select('deputado_id', \DB::raw('SUM(valor_liquido) as total_gasto'))
            ->whereIn('deputado_id', $deputadosIds)
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

    // public function deputadoDespesa(Request $request, $deputado_id)
    // {

    //     $page = $request->query('page', 1);
    //     $despesas = Despesa::where('deputado_id', $deputado_id)
    //                         ->orderBy('data_emissao', 'desc')
    //                         ->orderBy('valor_liquido', 'desc')

    //                         ->paginate(5, ['*'], 'page', $page);

    //     return response()->json($despesas);
    // }

    public function deputadoDespesa(Request $request, $deputado_id)
    {
        try {
            // Obter o número da página e itens por página
            $page = $request->query('page', 1);
            $perPage = 100; // Número de itens por página

            // Obter todas as despesas do deputado
            $despesas = Despesa::where('deputado_id', $deputado_id)
                ->orderBy('data_emissao', 'desc')
                ->get();

            // Agregar os valores por tipo de despesa
            $tipoDespesas = [];
            foreach ($despesas as $despesa) {
                $valor = (float) $despesa->valor_liquido;
                $tipoDespesa = $despesa->tipo_despesa;

                if (!isset($tipoDespesas[$tipoDespesa])) {
                    $tipoDespesas[$tipoDespesa] = [
                        'total' => $valor,
                        'id' => $despesa->id // Incluindo o ID da primeira despesa encontrada para o tipo
                    ];
                } else {
                    $tipoDespesas[$tipoDespesa]['total'] += $valor;
                }
            }

            // Converter tipo_despesas para array de despesas agregadas
            $resultado = [];
            foreach ($tipoDespesas as $tipo => $dados) {
                $resultado[] = [
                    'id' => $dados['id'], // ID da primeira despesa encontrada para o tipo
                    'tipo_despesa' => $tipo,
                    'total' => $dados['total']
                ];
            }

            // Ordenar pelo valor total em ordem decrescente
            usort($resultado, function ($a, $b) {
                return $b['total'] <=> $a['total'];
            });

            // Paginar os resultados
            $currentPage = Paginator::resolveCurrentPage();
            $currentItems = array_slice($resultado, ($currentPage - 1) * $perPage, $perPage);
            $paginator = new LengthAwarePaginator($currentItems, count($resultado), $perPage, $currentPage, [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]);

            return response()->json($paginator);

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Deputado não encontrado'], 404);
        }
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

        $topGastadores = Despesa::select('deputado_id', \DB::raw('SUM(valor_liquido) as total_gastos'))
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

    public function buscarNomeDeputado($nome)
    {

        $deputados = Deputado::where('nome', 'like', "%{$nome}%")->limit(6)->with('partido')->get();
        $deputados->each(function ($deputado) {
            $totalGastos = Despesa::where('deputado_id', $deputado->id)->sum('valor_liquido');
            $deputado->total_gastos = $totalGastos;
        });

        return response()->json($deputados);

    }

}
