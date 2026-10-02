<?php

return [
    'perfil' => [
        'tax_papfe'  => 'PAPFE',
        'tax_alumni' => 'Alumni',
        'tax_in'    => 'USP',
        'tax_ex'    => 'Externos',
        'tax_ter'   =>  'Terceira Idade',
        'tax_cepe'  =>  'CEPEUSP',
        'tax_child' => 'Dependente'
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
        'usp'     => 'Período de Inscrições Comunidade USP',
        'papfe'   => 'Período de Inscrições PAPFE',
        'cepeusp' => 'Período de Inscrições CEPE USP',
        'externa' => 'Período de Inscrições Comunidade Externa',
        'segundo' => 'Segundo Período de Inscrições',
        'curso'   => 'Período do Curso',
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
];
