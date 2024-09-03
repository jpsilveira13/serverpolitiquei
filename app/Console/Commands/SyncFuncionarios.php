<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Models\PublicEmployee;
use App\Models\EmployeeType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Models\Payment;

class SyncFuncionarios extends Command
{
    protected $signature = 'funcionarios:sync';
    protected $description = 'Synchronize funcionarios data from external API';

    public function handle()
    {
        $client = new Client();

        try {
            // Define a lista de vínculos a ser passada como parâmetro
            $listaVinculo = [

              "ESTAGIARIO"
            ];

            // "COMISSIONADO", -- ja foi
            // "AGENTE_POLITIVO", -- ja foi
            // "CONTRATADO",
            // "ESTAGIARIO",
            // "ESTAVEL",
            // "FUNCAO_PUBLICA",
            // "CONCURSADO",


            // Faz a requisição para o endpoint getFuncionarios
            $response = Http::timeout(120)->put('https://prefeitura.uberaba.mg.gov.br/GRP/portalcidadao/webservices/GFPFuncionario/getFuncionarios', [
                "limitaResultados" => false,
                'listaVinculo' => $listaVinculo,
                'status' => 'ATIVO'
            ]);

            $data = json_decode($response->getBody(), true);

            // Itera sobre os funcionários retornados
            foreach ($data as $funcionarioData) {
                // Verifica se o funcionário está ativo
                $active = ($funcionarioData['situacao'] === 'Ativo') ? true : false;

                // Obtém o tipo de funcionário pelo vínculo
                $employeeType = EmployeeType::where('name', $funcionarioData['vinculo'])->first();

                // Atualiza ou cria o registro do funcionário
                $funcionario = PublicEmployee::updateOrCreate(
                    ['matricula' => $funcionarioData['matricula']],
                    [
                        'matricula' => $funcionarioData['matriculaFormatada'],
                        'city_id' => 2204,
                        'active' => $active,
                        'nome' => $funcionarioData['nome'],
                        'documento' => $funcionarioData['documento'],
                        'employee_type_id' => $employeeType->id ?? null,
                    ]
                );

                if ($active) {
                    $this->processEmployeeDetails($client, $funcionario);
                    $this->updateEmployeeLocation($funcionario);
                    $this->info('Dados dos funcionários sincronizados com sucesso -> ' . $funcionario->matricula);
                }
            }

            $this->info('Dados dos funcionários sincronizados com sucesso.');
        } catch (\Exception $e) {
            $this->error('Erro ao sincronizar dados dos funcionários: ' . $e->getMessage());
        }
    }

    private function processEmployeeDetails($client, $funcionario)
    {
        try {
            // Faz a requisição para o endpoint getDadosFuncionarioPortal
            $response = Http::put('https://prefeitura.uberaba.mg.gov.br/GRP/portalcidadao/webservices/GFPFuncionario/getDadosFuncionarioPortal', [
                'val0' => $funcionario->matricula
            ]);

            $data = json_decode($response->getBody(), true);

            // Processa os dados adicionais do funcionário
            $funcionario->update([
                'admissao' => Carbon::createFromFormat('d/m/Y', $data['admissao'])->toDateString(),
                'cargo_funcao' => isset($data['cargo']) ? $data['cargo'] : (isset($data['funcao']) ? $data['funcao'] : null),
                'lotacao' => $data['lotacao'],
                'carga_horaria' => isset($data['cargaHoraria']) ? $data['cargaHoraria'] : null,
                // Outros campos conforme necessário
            ]);

            $monthMap = [
                'Janeiro' => '01',
                'Fevereiro' => '02',
                'Março' => '03',
                'Abril' => '04',
                'Maio' => '05',
                'Junho' => '06',
                'Julho' => '07',
                'Agosto' => '08',
                'Setembro' => '09',
                'Outubro' => '10',
                'Novembro' => '11',
                'Dezembro' => '12',
            ];

            // Processa os contra-cheques
            foreach ($data['listaContraCheque'] as $contraCheque) {
                $monthNumber = $monthMap[$contraCheque['mes']];
                $paymentMonth = Carbon::createFromFormat('Y-m', $contraCheque['ano'] . '-' . $monthNumber);

                $existingPayment = Payment::where('public_employee_id', $funcionario->id)
                    ->whereYear('payment_month', $paymentMonth->year)
                    ->whereMonth('payment_month', $paymentMonth->month)
                    ->first();

                if (!$existingPayment) {
                    $payment = new Payment();
                    $payment->public_employee_id = $funcionario->id;
                    $payment->payment_month = $paymentMonth->toDateString(); // Formato Y-m-d
                    $payment->amount = $contraCheque['proventos'];
                    $payment->descontos = $contraCheque['descontos'];
                    $payment->save();
                }
            }

        } catch (\Exception $e) {
            throw new \Exception('Erro ao obter dados do funcionário: ' . $e->getMessage());
        }
    }

    private function updateEmployeeLocation($funcionario)
    {
        $apiKey = 'AIzaSyDNMfMgDrFHdNRHlj6CWI0vYUh2F75a7Ic';
        $cleanedLocation = preg_replace('/^\d+\s*-\s*/', '', $funcionario->lotacao);
        $fullLocation = $cleanedLocation . ", Uberaba, MG";

        $response = Http::get("https://maps.googleapis.com/maps/api/place/findplacefromtext/json", [
            'input' => $fullLocation,
            'inputtype' => 'textquery',
            'fields' => 'formatted_address,geometry',
            'key' => $apiKey,
        ]);

        $data = json_decode($response->getBody(), true);

        if (isset($data['candidates']) && count($data['candidates']) > 0) {
            $candidate = $data['candidates'][0];
            $newAddress = $candidate['formatted_address'];
            $latitude = $candidate['geometry']['location']['lat'];
            $longitude = $candidate['geometry']['location']['lng'];

            // Atualiza somente se o endereço mudou ou se a latitude/longitude for nula
            if ($funcionario->local_trabalho !== $newAddress || $funcionario->latitude === null || $funcionario->longitude === null) {
                $funcionario->local_trabalho = $newAddress;
                $funcionario->latitude = $latitude;
                $funcionario->longitude = $longitude;
                $funcionario->save();
            }
        }
    }

}
