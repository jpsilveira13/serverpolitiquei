<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DeputadoController;
use App\Http\Controllers\Api\PartidoController;




Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/deputado/{slug}', [DeputadoController::class, 'deputado']);
Route::get('/deputado/{deputado_id}/despesas', [DeputadoController::class, 'deputadoDespesa']);
Route::get('/deputados/random', [DeputadoController::class, 'deputadosAleatorios']);
Route::get('/deputado/{deputado_id}/mandatosExternos', [DeputadoController::class, 'mandatosExternos']);
Route::get('/deputado/{deputado_id}/eventos', [DeputadoController::class, 'eventos']);
Route::get('/deputado/{deputado_id}/orgaos', [DeputadoController::class, 'orgaos']);



Route::get('/deputados', [DeputadoController::class, 'index']);
Route::get('/ranking', [DeputadoController::class, 'rankingGastadores']);



Route::get('/partidos', [PartidoController::class, 'index']);


// Route::get('/atualizar-slug', [DeputadoController::class, 'atualizarSlugs']);






