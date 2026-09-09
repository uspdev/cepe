<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\AtividadeController;
use App\Http\Controllers\OferecimentoController;
use App\Http\Controllers\TurmaController;

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