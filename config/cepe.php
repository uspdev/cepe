<?php

return [
    'perfil' => [
        'tax_papfe' => 'PAPFE',
        'tax_alumni' => 'Alumni',
        'tax_in' => 'USP',
        'tax_ex' => 'Externos',
        'tax_ter' => 'Terceira Idade',
        'tax_cepe' => 'CEPEUSP',
        'tax_child' => 'Dependente',
    ],
    'tipos_inscricao' => [
        'mensal' => 'Curso Mensal',
        'bimestral' => 'Curso Bimestral',
        'trimestral' => 'Curso Trimestral',
        'semestral' => 'Curso Semestral',
        'eventos' => 'Evento',
        'voltausp' => 'Volta USP',
        'voltinhausp' => 'Voltinha USP',
    ],
    'periodos' => [
        'usp' => 'Período de Inscrições Comunidade USP',
        'papfe' => 'Período de Inscrições PAPFE',
        'cepeusp' => 'Período de Inscrições CEPE USP',
        'alumni' => 'Período de Inscrições Alumni',
        'externa' => 'Período de Inscrições Comunidade Externa',
        'segundo' => 'Segundo Período de Inscrições',
        'curso' => 'Período do Curso',
    ],
    'tamanhos_camiseta' => [
        'voltausp' => [
            'pp' => 'PP', 'p' => 'P', 'm' => 'M', 'g' => 'G', 'gg' => 'GG', 'exg' => 'EXG',
        ],
        'voltinhausp' => [
            '6' => '6', '8' => '8', '10' => '10', '12' => '12', '14' => '14',
            'pp' => 'PP', 'p' => 'P', 'm' => 'M',
        ],
    ],
    'vagas' => [
        'padrao' => [
            'perfis' => ['tax_in', 'tax_papfe', 'tax_ex'],
            'tamanhos' => null,
            'reservadas' => false,
            'ano_nascimento' => false,
        ],
        'voltausp' => [
            'perfis' => ['tax_in', 'tax_alumni', 'tax_papfe', 'tax_ex'],
            'tamanhos' => 'voltausp',
            'reservadas' => true,
            'ano_nascimento' => false,
        ],
        'voltinhausp' => [
            'perfis' => ['tax_in'],
            'tamanhos' => 'voltinhausp',
            'reservadas' => true,
            'ano_nascimento' => true,
        ],
    ],
    'atestados' => [
        'tipos' => [
            'atestado_medico' => 'Atestado Médico',
            'exame_dermatologico' => 'Exame Dermatológico',
            'parq' => 'PAR-Q',
        ],
        'validade_meses' => [
            'atestado_medico' => 12,
            'exame_dermatologico' => 6,
            'parq' => 12,
        ],
        'status' => [
            'em_analise' => 'Em análise',
            'aprovado' => 'Aprovado',
            'reprovado' => 'Reprovado',
            'vencido' => 'Vencido',
        ],
        'arquivo' => [
            'mimes' => 'pdf,jpg,jpeg,png',
            'max_kb' => 5120,
        ],
        'parq' => [
            // faixa etária em que o PAR-Q substitui o atestado (exige data de nascimento no cadastro do usuário)
            'idade_min' => 15,
            'idade_max' => 69,
            'perguntas' => [
                1 => 'Algum médico já disse que você possui algum problema de coração e que só deveria realizar atividade física supervisionado por profissionais de saúde?',
                2 => 'Você sente dores no peito quando pratica atividade física?',
                3 => 'No último mês, você sentiu dores no peito quando praticou atividade física?',
                4 => 'Você apresenta desequilíbrio devido à tontura e/ ou perda de consciência?',
                5 => 'Você possui algum problema ósseo ou articular que poderia ser piorado pela atividade física?',
                6 => 'Você toma atualmente algum medicamento para pressão arterial e/ou problema de coração?',
                7 => 'Sabe de alguma outra razão pela qual você não deve praticar atividade física?',
            ],
            'termo' => 'Estou ciente de que é recomendável conversar com um médico antes de aumentar meu nível atual de atividade física, por ter respondido “SIM” a uma ou mais perguntas do “Questionário de Prontidão para Atividade Física” (PAR-Q). Assumo plena responsabilidade por qualquer atividade física praticada sem o atendimento a essa recomendação.',
        ],
    ],
];
