<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Oferecimento extends Model
{
    protected $fillable = [
        'atividade_id',
        'pagamento',
        'periodo',
        'atestado_medico',
        'exame_dermatologico',
        'limitar_matricula',
    ];

    protected $casts = [
        'atestado_medico' => 'boolean',
        'exame_dermatologico' => 'boolean',
        'limitar_matricula' => 'boolean',
    ];

    protected function formasPagamento(): Attribute
    {
        return Attribute::get(function () {
            $pagamento = $this->attributes['pagamento'] ?? null;
            $dados = $pagamento ? @unserialize($pagamento) : null;

            return is_array($dados) ? $dados : [];
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function atividade()
    {
        return $this->belongsTo(Atividade::class);
    }

    public function turmas()
    {
        return $this->hasMany(Turma::class);
    }

    public function periodos()
    {
        return $this->hasMany(PeriodoOferecimento::class);
    }

    /** Períodos de inscrição abertos neste momento (exceto 'curso'). */
    public function periodosAbertos()
    {
        return $this->periodos
            ->where('perfil', '<>', 'curso')
            ->filter(fn ($p) => $p->inicio <= now() && $p->fim >= now());
    }

    public function periodoCurso(): ?PeriodoOferecimento
    {
        return $this->periodos->firstWhere('perfil', 'curso');
    }

    /**
     * Oferecimento "vigente" de uma atividade: o que tem inscrições abertas,
     * senão o que está em período de curso, senão o mais recente.
     */
    public static function vigentePara(Atividade $atividade): ?self
    {
        $oferecimentos = $atividade->oferecimentos()->with('periodos')->get();

        return $oferecimentos->first(fn ($o) => $o->periodosAbertos()->isNotEmpty())
            ?? $oferecimentos->first(fn ($o) => ($p = $o->periodoCurso()) && $p->inicio <= now() && $p->fim >= now())
            ?? $oferecimentos->sortByDesc('id')->first();
    }
}