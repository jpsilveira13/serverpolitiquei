<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Models\Partido;

class ImportPartidos extends Command
{
    protected $signature = 'import:partidos';
    protected $description = 'Importa partidos da API da Câmara dos Deputados';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $client = new Client();
        $url = 'https://dadosabertos.camara.leg.br/api/v2/partidos?pagina=2&ordem=ASC&ordenarPor=sigla';
        
        do {
            $response = $client->get($url);
            $data = json_decode($response->getBody()->getContents(), true);
          

            foreach ($data['dados'] as $partidoData) {
                $detalhesResponse = $client->get($partidoData['uri']);
               
                $detalhesData = json_decode($detalhesResponse->getBody()->getContents(), true);
                $status = $detalhesData['dados']['status'];
                $lider = $status['lider'] ?? null;

                Partido::updateOrCreate(
                    ['id_partido' => $partidoData['id']],
                    [
                        'sigla' => $partidoData['sigla'],
                        'nome' => $partidoData['nome'],
                        'situacao' => $status['situacao'] ?? null,
                        'total_posse' => $status['totalPosse'] ?? null,
                        'total_membros' => $status['totalMembros'] ?? null,
                        'lider_nome' => $lider['nome'] ?? null,
                        'lider_uri' => $lider['uri'] ?? null,
                        'lider_uf' => $lider['uf'] ?? null,
                        'url_logo' => $detalhesData['dados']['urlLogo'] ?? null,
                    ]
                );
            }

            $url = $data['links']['proximos'][0]['href'] ?? null;

        } while ($url);

        $this->info('Partidos importados com sucesso.');
    }
}
