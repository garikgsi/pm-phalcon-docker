<?return [
    [
        'type' => 'title',
        'text' => 'Системные настройки'
    ],
    [
        'name'  => 'rcp_menu_sites',
        'type'  => 'link',
        'text'  => 'Сайты',
        'color' => 'blue',
        'icon'  => 'ic-earth',
        'href'  => '/sites',
        'level' => 3
    ],
    [
        'name'  => 'rcp_menu_controllers',
        'type'  => 'link',
        'text'  => 'Контроллеры',
        'color' => 'blue',
        'icon'  => 'ic-cog',
        'href'  => '/controllers',
        'level' => 3
    ],
    [
        'name'  => 'rcp_menu_apps',
        'type'  => 'link',
        'text'  => 'Приложения',
        'color' => 'blue',
        'icon'  => 'ic-play',
        'href'  => '/apps',
        'level' => 3
    ],
    [
        'name'  => 'rcp_menu_rights',
        'type'  => 'link',
        'text'  => 'Права',
        'color' => 'blue',
        'icon'  => 'ic-lock',
        'href'  => '/rights',
        'level' => 3
    ],
    [
        'name'  => '',
        'type'  => 'category',
        'text'  => 'Пользователи и группы',
        'color' => 'blue',
        'icon'  => 'ic-users',
        'opened' => 1,
        'list' => [
            [
                'name'  => 'rcp_menu_members',
                'type'  => 'link',
                'text'  => 'Пользователи',
                'color' => 'purple 300',
                'icon'  => 'ic-right',
                'href'  => '/members',
                'level' => 3
            ],
            [
                'name'  => 'rcp_menu_groups',
                'type'  => 'link',
                'text'  => 'Группы',
                'color' => 'purple 300',
                'icon'  => 'ic-right',
                'href'  => '/groups',
                'level' => 3
            ]
        ]
    ],
    [
        'name'  => '',
        'type'  => 'category',
        'text'  => 'Ресурсы',
        'color' => 'blue',
        'icon'  => 'ic-foldertree',
        'opened' => 1,
        'list' => [
            [
                'name'  => 'rcp_menu_resources_css',
                'type'  => 'link',
                'text'  => 'CSS',
                'color' => 'purple 300',
                'icon'  => 'ic-right',
                'href'  => '/resources/css',
                'level' => 3
            ],
            [
                'name'  => 'rcp_menu_resources_js',
                'type'  => 'link',
                'text'  => 'JavaScript',
                'color' => 'purple 300',
                'icon'  => 'ic-right',
                'href'  => '/resources/js',
                'level' => 3
            ]
        ]
    ]
];