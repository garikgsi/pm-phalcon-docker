<?return [
    [
        'type' => 'menu',
        'text' => 'Меню',
        'opened' => 1,
        'list' => [
            [
                'name'  => 'pm_menu_counteragents',
                'type'  => 'link',
                'text'  => 'Контрагенты',
                'color' => 'blue',
                'icon'  => 'users.svg',
                'href'  => '/counteragents',
                'level' => 3
            ],
            [
                'name'  => 'pm_menu_services',
                'type'  => 'link',
                'text'  => 'Партнерские услуги',
                'color' => 'blue',
                'icon'  => 'archive.svg',
                'icon_size'  => 'width:17px;',
                'href'  => '/services',
                'level' => 3
            ],
            [
                'name'  => 'pm_menu_contracts',
                'type'  => 'link',
                'text'  => 'Договора',
                'color' => 'blue',
                'icon'  => 'rules.svg',
                'href'  => '/contracts',
                'level' => 3
            ],
            [
                'name'  => 'pm_menu_partners',
                'type'  => 'link',
                'text'  => 'Заказы',
                'color' => 'blue',
                'icon'  => 'basket.svg',
                'href'  => '/orders',
                'level' => 3
            ]

        ]
    ],
    [
        'type' => 'dictionaries',
        'text' => 'Справочники',
        'opened' => 1,
        'list' => [
            [
                'name'  => 'pm_dict_opf',
                'type'  => 'link',
                'text'  => 'Организационно-правовые формы',
                'color' => 'blue',
                'icon'  => 'address.svg',
                'href'  => '/opf',
                'level' => 3
            ],
            [
                'name'  => 'pm_dict_international_segment',
                'type'  => 'link',
                'text'  => 'Сегменты сети',
                'color' => 'blue',
                'icon'  => 'earth.svg',
                'href'  => '/international_segments',
                'level' => 3
            ],
            [
                'name'  => 'pm_dict_provide_technology',
                'type'  => 'link',
                'text'  => 'Технологии предоставления услуг',
                'color' => 'blue',
                'icon'  => 'router.svg',
                'href'  => '/provide_technologies',
                'level' => 3
            ],
            [
                'name'  => 'pm_dict_contacts_roles',
                'type'  => 'link',
                'text'  => 'Роли контактов',
                'color' => 'blue',
                'icon'  => 'users1.svg',
                'href'  => '/contacts/roles',
                'level' => 3
            ],
            [
                'name'  => 'pm_dict_addresses',
                'type'  => 'link',
                'text'  => 'Адреса',
                'color' => 'blue',
                'icon'  => 'location1.svg',
                'href'  => '/addresses',
                'level' => 3
            ],
            [
                'name'  => 'pm_dict_services',
                'type'  => 'link',
                'text'  => 'Базовые услуги',
                'color' => 'blue',
                'icon'  => 'service.svg',
                'icon_size'  => 'width:17px;',
                'href'  => '/services/list',
                'level' => 3
            ],
            [
                'name'  => 'pm_dict_services_dicts',
                'type'  => 'link',
                'text'  => 'Свойства компонентов',
                'color' => 'blue',
                'icon'  => 'books.svg',
                'icon_size'  => 'width:18px;',
                'href'  => '/services/dicts',
                'level' => 3
            ],
            [
                'name'  => 'pm_dict_units',
                'type'  => 'link',
                'text'  => 'Единицы измерения',
                'color' => 'blue',
                'icon'  => 'design.svg',
                'href'  => '/units',
                'level' => 3
            ]
        ]

    ],
    [
        'type' => 'systems',
        'text' => 'Информационные системы',
        'opened' => 1,
        'list' => [
            [
                'name'  => 'pm_systems_tts',
                'type'  => 'link',
                'text'  => 'Система TTS',
                'color' => 'blue',
                'icon'  => 'hammer1.svg',
                'href'  => 'https://tts.naukanet.ru',
                'level' => 3
            ],
            [
                'name'  => 'pm_systems_sdn',
                'type'  => 'link',
                'text'  => 'Система SD',
                'color' => 'blue',
                'icon'  => 'briefcase.svg',
                'href'  => 'https://sdn.naukanet.ru',
                'level' => 3
            ],
            [
                'name'  => 'pm_systems_kpi',
                'type'  => 'link',
                'text'  => 'Портал KPI',
                'color' => 'blue',
                'icon'  => 'barchart.svg',
                'href'  => 'https://kpi2.naukanet.ru',
                'level' => 3
            ],
            [
                'name'  => 'pm_systems_pko',
                'type'  => 'link',
                'text'  => 'Портал КО',
                'color' => 'blue',
                'icon'  => 'insertbarchart.svg',
                'href'  => 'https://dwh.naukanet.ru:8078/Reports_VTTS/browse/',
                'level' => 3
            ]
        ]
    ],
];