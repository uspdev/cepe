<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IndexController;

use App\Http\Controllers\AtividadeController;
use App\Http\Controllers\OferecimentoController;
use App\Http\Controllers\TurmaController;

Route::get('/', [IndexController::class, 'index']);

# atividades
Route::get('/atividades', [AtividadeController::class, 'index']);
Route::get('/atividades/create', [AtividadeController::class, 'create']);
Route::post('/atividades', [AtividadeController::class, 'store']);
Route::get('/atividades/{atividade}', [AtividadeController::class, 'show']);
Route::get('/atividades/{atividade}/edit', [AtividadeController::class, 'edit']);
Route::patch('/atividades/{atividade}', [AtividadeController::class, 'update']);
Route::delete('/atividades/{atividade}', [AtividadeController::class, 'destroy']);


# oferecimentos
Route::get('/oferecimentos/{atividade}/create', [OferecimentoController::class, 'create']);

#Route::post('/oferecimentos', [OferecimentoController::class, 'store']);
#Route::get('/oferecimentos/{oferecimento}', [OferecimentoController::class, 'show']);
#Route::get('/oferecimentos/{oferecimento}/edit', [OferecimentoController::class, 'edit']);
#Route::patch('/oferecimentos/{oferecimento}', [OferecimentoController::class, 'update']);
#Route::delete('/oferecimentos/{oferecimento}', [OferecimentoController::class, 'destroy']);


Route::get('/turmas', [TurmaController::class, 'index']);
Route::get('/turmas/create', [TurmaController::class, 'create']);
Route::post('/turmas', [TurmaController::class, 'store']);
Route::get('/turmas/{turma}', [TurmaController::class, 'show']);
Route::get('/turmas/{turma}/edit', [TurmaController::class, 'edit']);
Route::patch('/turmas/{turma}', [TurmaController::class, 'update']);
Route::delete('/turmas/{turma}', [TurmaController::class, 'destroy']);
