<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Models\Deputado;
use App\Models\Partido;
use App\Models\Despesa;

class ImportDeputados extends Command
{
    protected $signature = 'import:deputados';
    protected $description = 'Importa dados dos deputados e suas despesas para o banco de dados';

    public function handle()
    {
        $client = new Client();
        $response = $client->get('https://dadosabertos.camara.leg.br/api/v2/deputados?ordem=ASC&ordenarPor=nome');
        $deputados = json_decode($response->getBody()->getContents(), true)['dados'];

        foreach ($deputados as $deputadoData) {
            $detalhesResponse = $client->get($deputadoData['uri']);
            $detalhes = json_decode($detalhesResponse->getBody()->getContents(), true)['dados'];


            $deputado = Deputado::updateOrCreate(
                ['deputado_id' => $detalhes['id']],
                [
                    'nome' => $detalhes['ultimoStatus']['nome'],
                    'sigla_uf' => $detalhes['ultimoStatus']['siglaUf'],
                    'id_legislatura' => $detalhes['ultimoStatus']['idLegislatura'],
                    'url_foto' => $detalhes['ultimoStatus']['urlFoto'],
                    'email' => $detalhes['ultimoStatus']['email'],
                    'nome_civil' => $detalhes['nomeCivil'],
                    'cpf' => $detalhes['cpf'],
                    'sexo' => $detalhes['sexo'],
                    'data_nascimento' => $detalhes['dataNascimento'],
                    'uf_nascimento' => $detalhes['ufNascimento'],
                    'municipio_nascimento' => $detalhes['municipioNascimento'],
                    'escolaridade' => $detalhes['escolaridade'],
                    'gabinete_nome' => $detalhes['ultimoStatus']['gabinete']['nome'],
                    'gabinete_predio' => $detalhes['ultimoStatus']['gabinete']['predio'],
                    'gabinete_sala' => $detalhes['ultimoStatus']['gabinete']['sala'],
                    'gabinete_andar' => $detalhes['ultimoStatus']['gabinete']['andar'],
                    'gabinete_telefone' => $detalhes['ultimoStatus']['gabinete']['telefone'],
                    'sigla_partido' => $detalhes['ultimoStatus']['siglaPartido'],
                    'rede_social' => json_encode($detalhes['redeSocial']),
                    'partido_id' => null
                ]
            );

            $this->importarDespesas($client, $deputado, 2022);
            $this->importarDespesas($client, $deputado, 2023);
            $this->importarDespesas($client, $deputado, 2024);

            $this->info('Deputado salvo: ' . $detalhes['ultimoStatus']['nome']);
        }

        $this->info('Dados dos deputados e suas despesas importados com sucesso!');
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
