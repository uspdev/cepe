<?php

use App\Http\Controllers\AtividadeController;
use App\Http\Controllers\Auth\CadastroController;
use App\Http\Controllers\Auth\LoginLocalController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\OferecimentoController;
use App\Http\Controllers\TurmaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Atestados\AtestadoController;
use App\Http\Controllers\Atestados\MeusAtestadosController;
use App\Http\Controllers\Carteiras\CarteiraController;
use App\Http\Controllers\Carteiras\MinhasCarteirasController;
use App\Http\Controllers\Carteiras\TaxaCarteiraController;
use App\Http\Controllers\Perfil\PerfilController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\FilaController;
use App\Http\Controllers\FrequenciaController;
use App\Http\Controllers\InscricaoController;
use App\Http\Controllers\MinhasInscricoesController;

Route::get('/', [IndexController::class, 'index'])->name('home');

// Login local e cadastro da comunidade externa (as rotas da senhaunica-socialite seguem intactas)
Route::middleware('guest')->group(function () {
    Route::get('/entrar', [LoginLocalController::class, 'create'])->name('entrar');
    Route::post('/entrar', [LoginLocalController::class, 'store'])->name('entrar.store');
    Route::get('/cadastro', [CadastroController::class, 'create'])->name('cadastro');
    Route::post('/cadastro', [CadastroController::class, 'store'])->name('cadastro.store');
});

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
    Route::post('/minhas-inscricoes/{matricula}/presenca', [FrequenciaController::class, 'checkin'])->name('minhas-inscricoes.presenca');
    Route::get('/minha-frequencia', [FrequenciaController::class, 'minhaFrequencia'])->name('minha-frequencia.index');

    Route::get('/minhas-turmas', [FrequenciaController::class, 'minhasTurmas'])->name('minhas-turmas.index');
    Route::get('/turmas/{turma}/frequencia', [FrequenciaController::class, 'index'])->name('frequencia.index');
    Route::get('/turmas/{turma}/frequencia/hoje', [FrequenciaController::class, 'hoje'])->name('frequencia.hoje');
    Route::get('/turmas/{turma}/frequencia/{chamada}', [FrequenciaController::class, 'show'])->name('frequencia.show');
    Route::patch('/turmas/{turma}/frequencia/{chamada}', [FrequenciaController::class, 'update'])->name('frequencia.update');
    Route::patch('/turmas/{turma}/frequencia/{chamada}/data', [FrequenciaController::class, 'alterarData'])->name('frequencia.data');
});



Route::middleware('auth')->group(function () {
    
    Route::get('/perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::patch('/perfil', [PerfilController::class, 'update'])->name('perfil.update');
    Route::get('/usuarios', [PerfilController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/{user}/perfil', [PerfilController::class, 'edit'])->name('usuarios.perfil.edit');
    Route::patch('/usuarios/{user}/perfil', [PerfilController::class, 'update'])->name('usuarios.perfil.update');

    Route::get('/meus-atestados', [MeusAtestadosController::class, 'index']);
    Route::get('/meus-atestados/create', [MeusAtestadosController::class, 'create']);
    Route::post('/meus-atestados', [MeusAtestadosController::class, 'store']);

    Route::get('/minhas-carteiras', [MinhasCarteirasController::class, 'index']);
    Route::get('/minhas-carteiras/create', [MinhasCarteirasController::class, 'create']);
    Route::post('/minhas-carteiras', [MinhasCarteirasController::class, 'store']);
    Route::get('/minhas-carteiras/{carteira}', [MinhasCarteirasController::class, 'show']);
    Route::post('/minhas-carteiras/{carteira}/documentos', [MinhasCarteirasController::class, 'reenviar']);
    Route::post('/perfil/carteira', [MinhasCarteirasController::class, 'vincular']);

    Route::get('/carteiras-taxas', [TaxaCarteiraController::class, 'edit']);
    Route::put('/carteiras-taxas', [TaxaCarteiraController::class, 'update']);

    Route::get('/carteiras', [CarteiraController::class, 'index']);
    Route::get('/carteiras/{carteira}', [CarteiraController::class, 'show']);
    Route::patch('/carteiras/{carteira}/analise', [CarteiraController::class, 'analise']);
    Route::get('/carteiras/{carteira}/documentos/{documento}', [CarteiraController::class, 'documento']);

    Route::get('/atestados', [AtestadoController::class, 'index']);
    Route::get('/atestados/exportar', [AtestadoController::class, 'exportar']);
    Route::get('/atestados/{atestado}', [AtestadoController::class, 'show']);
    Route::get('/atestados/{atestado}/arquivo', [AtestadoController::class, 'arquivo']);
    Route::patch('/atestados/{atestado}/analise', [AtestadoController::class, 'analise']);
    Route::delete('/atestados/{atestado}', [AtestadoController::class, 'destroy']);
});
