<?php

namespace App\Support;

class VinculoUsp
{
    private const ROTULOS = [
        'ALUNOGR' => 'Aluno de Graduação',
        'ALUNOPOS' => 'Aluno de Pós-Graduação',
        'ALUNOPD' => 'Pós-Doutorando',
        'ALUNOCEU' => 'Aluno de Cultura e Extensão',
        'ALUNOEAD' => 'Aluno EaD',
        'ALUNOCONVENIOINT' => 'Aluno de Convênio Internacional',
        'ESTAGIARIORH' => 'Estagiário',
        'SERVIDOR' => 'Servidor',
    ];

    /**
     * Resume os vínculos retornados pela Senha Única em rótulos de vínculo e de unidade.
     *
     * @return array{vinculo: ?string, unidade: ?string}
     */
    public static function resumir(iterable $vinculos): array
    {
        $tipos = [];
        $unidades = [];

        foreach ($vinculos as $v) {
            if (($v['tipvinext'] ?? null) === 'Servidor Designado') {
                continue;
            }

            $tipo = $v['tipoVinculo'] ?? null;
            $rotulo = ($v['tipoFuncao'] ?? null) === 'Docente'
                ? 'Docente'
                : (self::ROTULOS[$tipo] ?? ($tipo ? ucfirst(strtolower($tipo)) : null));
            if ($rotulo) {
                $tipos[$rotulo] = true;
            }

            $sigla = $v['siglaUnidade'] ?? null;
            $nome = $v['nomeUnidade'] ?? null;
            $unidade = $sigla && $nome ? "$sigla - $nome" : ($sigla ?: $nome);
            if ($unidade) {
                $unidades[$unidade] = true;
            }
        }

        return [
            'vinculo' => $tipos ? mb_substr(implode(', ', array_keys($tipos)), 0, 255) : null,
            'unidade' => $unidades ? mb_substr(implode('; ', array_keys($unidades)), 0, 255) : null,
        ];
    }
}
