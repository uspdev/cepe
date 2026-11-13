<?php

$atividades = [
    [
        'text' => 'Listar',
        'url' => config('app.url').'/atividades',
    ],
    [
        'text' => 'Cadastrar',
        'url' => config('app.url').'/atividades/create',
    ],
];

$menu = [
    // Público (catálogo)
    [
        'text' => '<i class="fas fa-home"></i> Home',
        'url' => '/',
    ],
    [
        // este item de menu será substituido no momento da renderização
        'key' => 'menu_dinamico',
    ],
    [
        'text' => 'Cursos',
        'url' => config('app.url').'/cursos',
        'can' => '',
    ],
    [
        'text' => 'Eventos',
        'url' => config('app.url').'/eventos',
        'can' => '',
    ],

    // Minha área
    [
        'text' => 'Minhas inscrições',
        'url' => config('app.url').'/minhas-inscricoes',
        'can' => 'user',
    ],
    [
        'text' => 'Minha frequência',
        'url' => config('app.url').'/minha-frequencia',
        'can' => 'user',
    ],
    [
        'text' => 'Minhas turmas',
        'url' => config('app.url').'/minhas-turmas',
        'can' => 'professor-turmas',
    ],
    [
        'text' => 'Meus atestados',
        'url' => config('app.url').'/meus-atestados',
        'can' => 'user',
    ],
    [
        'text' => 'Minhas carteiras',
        'url' => config('app.url').'/minhas-carteiras',
        'can' => 'user',
    ],

    // Administração
    [
        'text' => 'Atividades',
        'submenu' => $atividades,
        'can' => 'gerenciar-catalogo',
    ],
    [
        'text' => 'Atestados',
        'url' => config('app.url').'/atestados',
        'can' => 'analisar-atestados',
    ],
    [
        'text' => 'Pagamentos',
        'url' => config('app.url').'/boletos',
        'can' => 'pagamentos.gerenciar',
    ],
    [
        'text' => 'Carteiras',
        'submenu' => [
            ['text' => 'Solicitações', 'url' => config('app.url').'/carteiras', 'can' => 'carteiras.analisar'],
            ['text' => 'Taxas', 'url' => config('app.url').'/carteiras-taxas', 'can' => 'taxas.gerenciar'],
        ],
        'can' => 'carteiras.analisar',
    ],
    [
        'text' => 'Usuários',
        'url' => config('app.url').'/usuarios',
        'can' => 'perfis.gerenciar',
    ],
];

$right_menu = [
    [
        // menu utilizado para views da biblioteca senhaunica-socialite.
        'key' => 'senhaunica-socialite',
    ],
    [
        'text' => '<i class="fas fa-user"></i> Meu perfil',
        'title' => 'Meu perfil',
        'url' => config('app.url').'/perfil',
        'align' => 'right',
        'can' => 'user',
    ],
    [
        'key' => 'laravel-tools',
    ],
];

return [
    // valor default para a tag title, dentro da section title.
    // valor pode ser substituido pela aplicação.
    'title' => config('app.name'),

    // USP_THEME_SKIN deve ser colocado no .env da aplicação
    'skin' => env('USP_THEME_SKIN', 'uspdev'),

    // chave da sessão. Troque em caso de colisão com outra variável de sessão.
    'session_key' => 'laravel-usp-theme',

    // usado na tag base, permite usar caminhos relativos nos menus e demais elementos html
    // na versão 1 era dashboard_url
    'app_url' => config('app.url'),

    // login e logout
    'logout_method' => 'POST',
    'logout_url' => 'logout',
    'login_url' => 'entrar',

    // menus
    'menu' => $menu,
    'right_menu' => $right_menu,

    'mensagensFlash' => true,

    // container ou container-fluid
    'container' => 'container-fluid',

];
