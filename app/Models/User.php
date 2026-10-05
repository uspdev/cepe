<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
