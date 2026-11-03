<?php

namespace App\Services;

use App\Models\Chamada;
use App\Models\Turma;

class FrequenciaService
{
    private const DIAS = ['segunda' => 1, 'terca' => 2, 'quarta' => 3, 'quinta' => 4, 'sexta' => 5, 'sabado' => 6, 'domingo' => 7];

    public function janelaAberta(Turma $turma): bool
    {
        $agora = now(config('cepe.fuso_frequencia'));
        $dia = array_search($agora->dayOfWeekIso, self::DIAS, true);

        if (! $turma->horario_inicio || ! $turma->horario_fim || ! in_array($dia, $turma->dias_semana ?? [], true)) {
            return false;
        }

        $hora = $agora->format('H:i');

        return $hora >= substr($turma->horario_inicio, 0, 5) && $hora <= substr($turma->horario_fim, 0, 5);
    }

    public function chamadaDeHoje(Turma $turma, ?int $userId = null): Chamada
    {
        $hoje = now(config('cepe.fuso_frequencia'))->toDateString();

        return $turma->chamadas()->whereDate('data', $hoje)->first()
            ?? $turma->chamadas()->create(['data' => $hoje, 'user_id' => $userId]);
    }
}
