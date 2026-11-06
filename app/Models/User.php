<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Traits\HasRoles;
use Uspdev\SenhaunicaSocialite\Traits\HasSenhaunica;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use HasRoles;
    use HasSenhaunica;

    public function perfil()
    {
        return $this->hasOne(Perfil::class);
    }

    public function perfilOuNovo(): Perfil
    {
        return $this->perfil ?? $this->perfil()->make();
    }

    /** Tipos de atividade dos grupos (`cepe.grupos_catalogo`) em que o usuário atua, conforme `grupo.*`. */
    public function tiposPermitidos(): array
    {
        $gate = Gate::forUser($this);

        return collect(config('cepe.grupos_catalogo'))
            ->filter(fn ($tipos, $grupo) => $gate->allows("grupo.{$grupo}"))
            ->flatten()
            ->values()
            ->all();
    }

    /** Se pode exercer a permission sobre atividades do tipo (ou de algum grupo, sem tipo). */
    public function gerencia(string $permissao, ?string $tipo = null): bool
    {
        if (! Gate::forUser($this)->allows($permissao)) {
            return false;
        }

        $tipos = $this->tiposPermitidos();

        return $tipo === null ? $tipos !== [] : in_array($tipo, $tipos);
    }

    /** Tipos de documento (`atestados.tipos`) que o usuário pode analisar. */
    public function tiposAtestadoAnalisaveis(): array
    {
        $gate = Gate::forUser($this);

        return array_keys(array_filter(config('cepe.atestados_permissoes'), fn ($permissao) => $gate->allows($permissao)));
    }

    /** Tipos de documento que o usuário pode enviar (PAR-Q só dentro da faixa etária conhecida). */
    public function tiposAtestado(): array
    {
        $tipos = config('cepe.atestados.tipos');
        if ($this->perfilOuNovo()->parqPermitido() === false) {
            unset($tipos['parq']);
        }

        return $tipos;
    }

    public function atestados()
    {
        return $this->hasMany(Atestado::class);
    }

    public function matriculas()
    {
        return $this->hasMany(Matricula::class);
    }

    public function filas()
    {
        return $this->hasMany(Fila::class);
    }

    /** Carteiras CEPEUSP solicitadas pelo usuário como titular (para si ou para dependentes). */
    public function carteirasSolicitadas()
    {
        return $this->hasMany(Carteira::class, 'solicitante_id');
    }

    /** Carteiras CEPEUSP das quais o usuário é o beneficiário vinculado. */
    public function carteiras()
    {
        return $this->hasMany(Carteira::class);
    }

    public function turmasComoProfessor()
    {
        return $this->hasMany(Turma::class, 'professor_id');
    }

    public function scopeProfessores($query)
    {
        return $query->whereHas('roles', fn ($q) => $q->where('name', 'professor'));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
