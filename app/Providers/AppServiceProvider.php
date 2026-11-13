<?php

namespace App\Providers;

use App\Contracts\BoletoGateway;
use App\Contracts\PixGateway;
use App\Models\Cobranca;
use App\Models\SolicitacaoCarteira;
use App\Models\User;
use App\Policies\CobrancaPolicy;
use App\Policies\PerfilPolicy;
use App\Policies\SolicitacaoCarteiraPolicy;
use App\Services\UspdevBoletoGateway;
use App\Services\UspdevPixGateway;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BoletoGateway::class, UspdevBoletoGateway::class);
        $this->app->bind(PixGateway::class, UspdevPixGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, PerfilPolicy::class);
        Gate::policy(SolicitacaoCarteira::class, SolicitacaoCarteiraPolicy::class);
        Gate::policy(Cobranca::class, CobrancaPolicy::class);

        // Permissions da aplicação (guard web): o Spatie já libera quem as tem (via role);
        // aqui o `admin` da senhaunica-socialite passa em todas.
        foreach (config('cepe.permissoes') as $permissao) {
            Gate::define($permissao, fn (User $user) => Gate::forUser($user)->allows('admin') || $user->checkPermissionTo($permissao));
        }

        // Gates de menu e contexto
        Gate::define('gerenciar-catalogo', fn (User $user) => $user->gerencia('atividades.gerenciar'));
        Gate::define('analisar-atestados', fn (User $user) => $user->tiposAtestadoAnalisaveis() !== []);
        Gate::define('professor-turmas', fn (User $user) => $user->turmasComoProfessor()->exists());

        // Aluno = usuário sem nenhum papel da aplicação. Serve ao menu/contexto; o acesso aos
        // dados (matrículas, presença) segue policies de dono, para que equipe também possa se inscrever.
        Gate::define('aluno', fn (User $user) => $user->roles->isEmpty());
    }
}
