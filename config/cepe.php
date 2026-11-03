<?php

return [
    'status_atividade' => [
        'ativo' => 'Ativo',
        'inativo' => 'Inativo',
        'excluido' => 'Excluído',
    ],
    'perfil' => [
        'tax_papfe' => 'PAPFE',
        'tax_alumni' => 'Alumni',
        'tax_in' => 'USP',
        'tax_ex' => 'Externos',
        'tax_ter' => 'Terceira Idade',
        'tax_cepe' => 'CEPEUSP',
        'tax_child' => 'Dependente',
        'tax_con' => 'Conveniado',
    ],
    // perfis sem vagas próprias na turma usam o bucket do perfil indicado
    'vaga_fallback' => [
        'tax_child' => 'tax_in',
        'tax_con' => 'tax_in',
    ],
    // perfis sem taxa própria na turma usam a taxa do perfil indicado
    'taxa_fallback' => [
        'tax_con' => 'tax_in',
    ],
    // vínculos da Senha Única que dão direito aos perfis USP (tax_in e tax_papfe); ALUMNI tem perfil próprio
    'vinculos_usp_permitidos' => [
        'ALUNOCOTEL', 'ALUNOEA', 'ALUNOEAD', 'ALUNOESPGR', 'ALUNOGR', 'ALUNOPD', 'ALUNOPOS',
        'DOCENTE', 'PESQUISADORCOLAB', 'PROVISITANTERNAC', 'PROFVISITANTE', 'SERVIDOR',
    ],
    // taxas específicas por vínculo USP (código da Senha Única), configuráveis na turma; só para os tipos de atividade listados
    'taxa_vinculo' => [
        'tipos' => ['voltausp'],
        'perfis' => ['tax_in'],
        'vinculos' => [
            'ALUNOGR' => 'Aluno de Graduação',
        ],
    ],
    // cursos: desconto a partir do segundo curso no mesmo período
    'desconto_segundo_curso' => [
        'percentuais' => ['tax_child' => 0.5, 'tax_ter' => 0.5, 'tax_ex' => 0.2],
        'minimo' => 5,
    ],
    // valor mínimo cobrado quando o crédito não cobre toda a taxa
    'taxa_minima' => 5,
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
    'periodo_perfis' => [
        'usp' => ['tax_in', 'tax_child', 'tax_con'],
        'papfe' => ['tax_papfe'],
        'cepeusp' => ['tax_cepe'],
        'alumni' => ['tax_alumni'],
        'externa' => ['tax_ex'],
        'segundo' => ['tax_in', 'tax_papfe', 'tax_ex', 'tax_alumni', 'tax_cepe', 'tax_ter', 'tax_child', 'tax_con'],
        'curso' => [],
    ],
    'status_matricula' => [
        'matriculada' => 'Matriculada',
        'cancelada' => 'Cancelada',
    ],
    'status_presenca' => [
        'presente' => 'Presente',
        'falta' => 'Falta',
        'justificada' => 'Justificada',
    ],
    'fuso_frequencia' => 'America/Sao_Paulo',
    'grupos_catalogo' => [
        'cursos' => ['mensal', 'bimestral', 'trimestral', 'semestral'],
        'eventos' => ['eventos', 'voltausp', 'voltinhausp'],
    ],
    // permissions da aplicação (guard web); `grupo.*` delimita os grupos de `grupos_catalogo` em que o papel atua
    'permissoes' => [
        'atividades.gerenciar',
        'inscricoes.gerenciar',
        'frequencia.gerenciar',
        'atestados.analisar',
        'atestados.dermatologico',
        'perfis.gerenciar',
        'grupo.cursos',
        'grupo.eventos',
    ],
    // roles da aplicação (guard web) e suas permissions; sincronizadas por `php artisan cepe:papeis`
    'papeis' => [
        'sru' => ['perfis.gerenciar', 'atestados.analisar', 'inscricoes.gerenciar', 'frequencia.gerenciar', 'grupo.cursos', 'grupo.eventos'],
        'gestor-cursos' => ['atividades.gerenciar', 'inscricoes.gerenciar', 'frequencia.gerenciar', 'grupo.cursos'],
        'gestor-eventos' => ['atividades.gerenciar', 'inscricoes.gerenciar', 'grupo.eventos'],
        'avaliador-dermatologico' => ['atestados.dermatologico'],
        'professor' => [],
    ],
    // permission necessária para analisar cada tipo de documento (`atestados.tipos`)
    'atestados_permissoes' => [
        'atestado_medico' => 'atestados.analisar',
        'parq' => 'atestados.analisar',
        'exame_dermatologico' => 'atestados.dermatologico',
    ],
    'limite_cursos' => [
        'primeiro' => 1,
        'total' => 10,
    ],
    // segundos sem heartbeat após os quais uma entrada da fila de inscrição deixa de bloquear os demais
    'fila_ttl' => 120,
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
            'excluir_perfis' => ['tax_child', 'tax_con'],
        ],
        'voltinhausp' => [
            'perfis' => ['tax_in'],
            'tamanhos' => 'voltinhausp',
            'reservadas' => true,
            'ano_nascimento' => true,
            'excluir_perfis' => ['tax_child', 'tax_con'],
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
    'estados' => [
        'AC' => 'Acre',
        'AL' => 'Alagoas',
        'AM' => 'Amazonas',
        'AP' => 'Amapá',
        'BA' => 'Bahia',
        'CE' => 'Ceará',
        'DF' => 'Distrito Federal',
        'ES' => 'Espírito Santo',
        'GO' => 'Goiás',
        'MA' => 'Maranhão',
        'MT' => 'Mato Grosso',
        'MS' => 'Mato Grosso do Sul',
        'MG' => 'Minas Gerais',
        'PA' => 'Pará',
        'PB' => 'Paraíba',
        'PR' => 'Paraná',
        'PE' => 'Pernambuco',
        'PI' => 'Piauí',
        'RJ' => 'Rio de Janeiro',
        'RN' => 'Rio Grande do Norte',
        'RO' => 'Rondônia',
        'RS' => 'Rio Grande do Sul',
        'RR' => 'Roraima',
        'SC' => 'Santa Catarina',
        'SE' => 'Sergipe',
        'SP' => 'São Paulo',
        'TO' => 'Tocantins',
    ],
];
