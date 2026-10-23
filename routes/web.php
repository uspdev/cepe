<?php

use App\Http\Controllers\AtividadeController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\OferecimentoController;
use App\Http\Controllers\TurmaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Atestados\AtestadoController;
use App\Http\Controllers\Atestados\MeusAtestadosController;
use App\Http\Controllers\Perfil\PerfilController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\FilaController;
use App\Http\Controllers\InscricaoController;
use App\Http\Controllers\MinhasInscricoesController;

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

// Catálogo (desenvolvimento: vitrine de cursos e eventos)
Route::get('/cursos', [CatalogoController::class, 'cursos'])->name('catalogo.cursos');
Route::get('/eventos', [CatalogoController::class, 'eventos'])->name('catalogo.eventos');
Route::get('/{atividade}', [CatalogoController::class, 'show'])->name('catalogo.show')->whereNumber('atividade');

// Inscrições e área do usuário
Route::middleware('auth')->group(function () {
    Route::get('/inscricoes/{turma}', [InscricaoController::class, 'create'])->name('inscricoes.create');
    Route::post('/inscricoes/{turma}', [InscricaoController::class, 'store'])->name('inscricoes.store');
    Route::post('/inscricoes/{turma}/fila', [FilaController::class, 'entrar'])->name('filas.entrar');
    Route::get('/inscricoes/{turma}/fila/status', [FilaController::class, 'status'])->name('filas.status');
    Route::get('/minhas-inscricoes', [MinhasInscricoesController::class, 'index'])->name('minhas-inscricoes.index');
    Route::patch('/minhas-inscricoes/{matricula}/cancelar', [MinhasInscricoesController::class, 'cancelar'])->name('minhas-inscricoes.cancelar');
});



Route::middleware('auth')->group(function () {
    Route::get('/perfil', [PerfilController::class, 'edit']);
    Route::patch('/perfil', [PerfilController::class, 'update']);
    Route::get('/usuarios/{user}/perfil', [PerfilController::class, 'edit']);
    Route::patch('/usuarios/{user}/perfil', [PerfilController::class, 'update']);

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
