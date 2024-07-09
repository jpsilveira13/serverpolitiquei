<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CitiesTableSeeder extends Seeder
{
    public function run()
    {
        $json = File::get(database_path('data/estados-cidades.json'));
        $data = json_decode($json, true);

        foreach ($data['estados'] as $state) {
            $stateId = DB::table('states')->where('abbreviation', $state['sigla'])->value('id');
            foreach ($state['cidades'] as $city) {
                DB::table('cities')->insert([
                    'name' => $city,
                    'state_id' => $stateId
                ]);
            }
        }
    }
}
