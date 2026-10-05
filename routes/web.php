<?php

use App\Http\Controllers\AtividadeController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\OferecimentoController;
use App\Http\Controllers\TurmaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [IndexController::class, 'index'])->name('home');

// Atividades
Route::controller(AtividadeController::class)->prefix('atividades')->name('atividades.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{atividade}', 'show')->name('show');
    Route::get('/{atividade}/edit', 'edit')->name('edit');
    Route::patch('/{atividade}', 'update')->name('update');
    Route::delete('/{atividade}', 'destroy')->name('destroy');
});

// Oferecimentos
Route::controller(OferecimentoController::class)->prefix('oferecimentos')->name('oferecimentos.')->group(function () {
    Route::get('/{atividade}/create', 'create')->name('create');
    Route::post('/{atividade}', 'store')->name('store');
    Route::get('/{atividade}/{oferecimento}', 'show')->name('show');
    Route::get('/{atividade}/{oferecimento}/edit', 'edit')->name('edit');
    Route::patch('/{atividade}/{oferecimento}', 'update')->name('update');
    Route::delete('/{atividade}/{oferecimento}', 'destroy')->name('destroy');
});

// Turmas
Route::controller(TurmaController::class)->prefix('turmas')->name('turmas.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{turma}', 'show')->name('show');
    Route::get('/{turma}/edit', 'edit')->name('edit');
    Route::patch('/{turma}', 'update')->name('update');
    Route::delete('/{turma}', 'destroy')->name('destroy');
});
use App\Http\Controllers\MatriculaController;

Route::get('/matriculas', [MatriculaController::class, 'index']);
Route::get('/matriculas/create', [MatriculaController::class, 'create']);
Route::post('/matriculas', [MatriculaController::class, 'store']);
Route::get('/matriculas/{matricula}', [MatriculaController::class, 'show']);
Route::get('/matriculas/{matricula}/edit', [MatriculaController::class, 'edit']);
Route::patch('/matriculas/{matricula}', [MatriculaController::class, 'update']);
Route::delete('/matriculas/{matricula}', [MatriculaController::class, 'destroy']);

use App\Http\Controllers\Atestados\AtestadoController;
use App\Http\Controllers\Atestados\MeusAtestadosController;

Route::middleware('auth')->group(function () {
    Route::get('/meus-atestados', [MeusAtestadosController::class, 'index']);
    Route::get('/meus-atestados/create', [MeusAtestadosController::class, 'create']);
    Route::post('/meus-atestados', [MeusAtestadosController::class, 'store']);

    Route::get('/atestados', [AtestadoController::class, 'index']);
    Route::get('/atestados/exportar', [AtestadoController::class, 'exportar']);
    Route::get('/atestados/{atestado}', [AtestadoController::class, 'show']);
    Route::get('/atestados/{atestado}/arquivo', [AtestadoController::class, 'arquivo']);
    Route::patch('/atestados/{atestado}/analise', [AtestadoController::class, 'analise']);
    Route::delete('/atestados/{atestado}', [AtestadoController::class, 'destroy']);
});
