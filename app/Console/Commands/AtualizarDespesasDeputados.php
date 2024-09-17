<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Models\Deputado;
use App\Models\Despesa;

class AtualizarDespesasDeputados extends Command
{
    protected $signature = 'atualizar:despesas';
    protected $description = 'Atualiza as despesas dos deputados no banco de dados';

    public function handle()
    {
        $deputados = Deputado::all();

        $client = new Client();

        foreach ($deputados as $deputado) {
            // $this->importarDespesas($client, $deputado, 2022);
            // $this->importarDespesas($client, $deputado, 2023);
            $this->importarDespesas($client, $deputado, 2024);

            $this->info('Despesas atualizadas para o deputado: ' . $deputado->nome);
        }

        $this->info('Despesas dos deputados atualizadas com sucesso!');
    }

    private function importarDespesas(Client $client, Deputado $deputado, int $ano)
    {
        $pagina = 1;
        do {
            $response = $client->get("https://dadosabertos.camara.leg.br/api/v2/deputados/{$deputado->deputado_id}/despesas?ano={$ano}&pagina={$pagina}&ordem=asc&ordenarPor=valorDocumento");
            $despesas = json_decode($response->getBody()->getContents(), true)['dados'];

            foreach ($despesas as $despesaData) {
                Despesa::updateOrCreate(
                    ['documento_id' => $despesaData['codDocumento']],
                    [
                        'deputado_id' => $deputado->id,
                        'ano' => $despesaData['ano'],
                        'mes' => $despesaData['mes'],
                        'tipo_despesa' => $despesaData['tipoDespesa'],
                        'cnpj_cpf_fornecedor' => $despesaData['cnpjCpfFornecedor'],
                        'fornecedor' => $despesaData['nomeFornecedor'],
                        'valor_documento' => $despesaData['valorDocumento'],
                        'valor_liquido' => $despesaData['valorLiquido'],
                        'data_emissao' => $despesaData['dataDocumento'],
                        'url_documento' => $despesaData['urlDocumento'],
                    ]
                );
            }

            $pagina++;
        } while (!empty($despesas));
    }
}
