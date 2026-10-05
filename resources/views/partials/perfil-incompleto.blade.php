@auth
    @php($faltam = auth()->user()->perfilOuNovo()->camposFaltantes())
    @if($faltam && ! request()->is('perfil'))
        <div class="alert alert-warning">
            Seu perfil está incompleto ({{ implode(', ', $faltam) }}).
            <a href="/perfil" class="alert-link">Completar perfil</a>
        </div>
    @endif
@endauth
