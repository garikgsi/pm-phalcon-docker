<?php
namespace Site\Front\Controllers;


use Core\Extender\ControllerApp;
use Site\Front\Handlers\ContactsHandler;
use Site\Front\Handlers\ContractsHandler;
use Site\Front\Handlers\FilesHandler;

/**
 * Class CounteragentsController
 * @package Site\Front\Controllers
 */
class CounteragentsController extends ControllerApp
{
    const ITEMS_PER_PAGE = 50;
    const PAGE_LEFT_RIGHT_DELTA = 3;

    public function initialize()
    {
        parent::initialize();

        $this->hapi->setHapiController('counteragents');
    }

    public function indexAction()
    {
        if ($this->request->isPost() && $this->request->isAjax() && (int)$this->request->get('filter_add') == 1) {
            $this->linkAdd();
        }

        $this->view->setVar('mainMenuData', [
            'active' => 'pm_menu_counteragents',
            'overlay' => 1,
            'template' => 'counteragents/menu_index',
            'template_panels' => 'counteragents/panels_index',
            'icon' => 'users.svg',
            'title' => 'Контрагенты',
        ]);

        $this->hapi->setHapiAction('index');

        $result = $this->searchEngine();

        $filtersMap = $result['filters'];
        $sortingMap = $result['sorting'];
        $columnsMap = $result['columns'];

        $sortingList = $result['sorting_list'];
        $columnsList = $result['columns_list'];

        $residents = $this->db->query(
        '
                WITH counts AS (
                     SELECT COUNT(*) AS resident_count
                          , c.counteragent_resident_id
                     FROM pm.counteragent AS c 
                     GROUP BY c.counteragent_resident_id
                )
                SELECT cr.counteragent_resident_id AS resident_id
                     , cr.resident_title
                     , COALESCE(cs.resident_count, 0) AS resident_count
                FROM pm.counteragent_resident AS cr
                     LEFT JOIN counts AS cs  ON cs.counteragent_resident_id = cr.counteragent_resident_id
                ORDER BY cr.resident_order 
            '
        );

        $residentsFilterList = [];

        while($row = $residents->fetch()) {
            $residentsFilterList[] = [
                'name' => 'resident_'.$row['resident_id'],
                'count' => $row['resident_count'],
                'title' => $row['resident_title'],
                'checked' => ($filtersMap['resident'] ?? 0) == $row['resident_id'],
            ];
        }

        $statuses = $this->db->query(
            '
                WITH counts AS (
                     SELECT COUNT(*) AS status_count
                          , c.counteragent_status_id
                     FROM pm.counteragent AS c 
                     GROUP BY c.counteragent_status_id
                )
                SELECT cs.counteragent_status_id AS status_id
                     , cs.status_title
                     , cs.status_name
                     , COALESCE(ct.status_count, 0) AS status_count
                FROM pm.counteragent_status AS cs
                     LEFT JOIN counts AS ct  ON ct.counteragent_status_id = cs.counteragent_status_id
                ORDER BY cs.status_order 
            '
        );

        $statusesFilterList = [];

        while($row = $statuses->fetch()) {
            $statusesFilterList[] = [
                'name' =>  $row['status_name'],
                'count' => $row['status_count'],
                'title' => $row['status_title'],
                'checked' => $filtersMap['status'][$row['status_name']] ?? 0,
            ];
        }

        $opfList = $filtersMap['opf'] ?? [];
        $opfTileList = $this->searchEngineOpfPrepareMenuTiles($opfList);

        $managerList = $filtersMap['manager'] ?? [];
        $managerTileList = $this->searchEngineManagerPrepareMenuTiles($managerList);

        $groupList = $filtersMap['group'] ?? [];
        $groupTileList = $this->searchEngineGroupPrepareMenuTiles($groupList);

        $segmentList = $filtersMap['segment'] ?? [];
        $segmentTileList = $this->searchEngineSegmentPrepareMenuTiles($segmentList);

        $technologyList = $filtersMap['technology'] ?? [];
        $technologyCatList = $filtersMap['technology_cat'] ?? [];
        $technologyTileList = $this->searchEngineTechnologyPrepareMenuTiles($technologyList, $technologyCatList);


        $valTitle = $filtersMap['title'] ?? '';
        $valInn   = $filtersMap['inn']   ?? '';
        $valKpp   = $filtersMap['kpp']   ?? '';

        $elizaSearch = [
            'filter_inputs' => [
                ['filter' => 'input', 'value' => $valTitle, 'name' => 'title', 'title' => 'Название', 'placeholder' => 'Название контрагента', 'position' => 'top'],
                ['filter' => 'input', 'value' => $valInn,   'name' => 'inn',   'title' => 'ИНН',      'placeholder' => 'ИНН контрагента',      'position' => 'bottom'],
                //['filter' => 'input', 'value' => $valKpp,   'name' => 'kpp',   'title' => 'КПП',      'placeholder' => 'КПП контрагента',      'position' => 'bottom'],
            ],
            'filter_items' => [
                ['filter' => 'switch',     'name' => 'only_mine',  'title' => 'Мои контрагенты', 'checked' => $filtersMap['only_mine'] ?? 0],
                ['filter' => 'check',      'name' => 'status',     'title' => 'Статус',        'list' => $statusesFilterList],
                ['filter' => 'radio_flex', 'name' => 'resident',   'title' => 'Резидентство',  'list' => $residentsFilterList],
                ['filter' => 'extend',     'name' => 'manager',    'title' => 'Менеджеры',      'category' => 0, 'extended' => ['active' => 1, 'title' => 'Выбор менеджеров'],             'reset' => ['active' => sizeof($managerList),    'title' => 'Cброс', 'descr' => 'Снять всех выбранных менеджеров'],            'icon' => 'user',       'list' => $managerTileList],
                ['filter' => 'extend',     'name' => 'group',      'title' => 'Подразделения',  'category' => 0, 'extended' => ['active' => 1, 'title' => 'Выбор подразделений'],          'reset' => ['active' => sizeof($groupList),      'title' => 'Cброс', 'descr' => 'Снять все выбранные подразделения'],          'icon' => 'users2 s20', 'list' => $groupTileList],
                ['filter' => 'extend',     'name' => 'segment',    'title' => 'Сегменты сети',  'category' => 0, 'extended' => ['active' => 1, 'title' => 'Выбор сегментов'],              'reset' => ['active' => sizeof($segmentList),    'title' => 'Cброс', 'descr' => 'Снять все выбранные сегменты'],               'icon' => 'earth',      'list' => $segmentTileList],
                ['filter' => 'extend',     'name' => 'technology', 'title' => 'Технологии',     'category' => 1, 'extended' => ['active' => 1, 'title' => 'Выбор технологий подключения'], 'reset' => ['active' => sizeof($technologyList) || sizeof($technologyCatList), 'title' => 'Cброс', 'descr' => 'Снять все выбранные технологии подключения'], 'icon' => 'router',     'list' => $technologyTileList],
                ['filter' => 'extend',     'name' => 'opf',        'title' => 'Правовые формы', 'category' => 0, 'extended' => ['active' => 1, 'title' => 'Выбор правовых форм'],          'reset' => ['active' => sizeof($opfList),        'title' => 'Cброс', 'descr' => 'Снять все выбранные провавые формы'],         'icon' => 'table',      'list' => $opfTileList],
            ],
            'control_sections' => [
                ['control' => 'orders',  'name' => 'sorting', 'title' => 'Сортировка', 'list' => $sortingList],
                ['control' => 'columns', 'name' => 'columns', 'title' => 'Столбцы',    'list' => $columnsList],
            ]
        ];

        $this->view->setVar('searchEngineData', $elizaSearch);


        $this->view->setVar('searchEnginePanelsList', [
            ['type' => 'panel_search', 'data' => ['name' => 'manager',    'icon' => 'user',       'layout' => 'columns_1', 'width' => 'w960', 'height' => 'h640', 'placeholder' => 'Поиск менеджера']],
            ['type' => 'panel_title',  'data' => ['name' => 'group',      'icon' => 'users2 s20', 'layout' => 'columns_1', 'width' => 'w960', 'height' => 'h640', 'title' => 'Выбор подразделения']],
            ['type' => 'panel_title',  'data' => ['name' => 'segment',    'icon' => 'earth',      'layout' => 'columns_1', 'width' => 'w960', 'height' => 'h640', 'title' => 'Выбор международного сегмента']],
            ['type' => 'panel_title',  'data' => ['name' => 'technology', 'icon' => 'router',     'layout' => 'columns_2', 'width' => 'w960', 'height' => 'h640', 'title' => 'Выбор технологии предоставления услуг']],
            ['type' => 'panel_title',  'data' => ['name' => 'opf',        'icon' => 'table',      'layout' => 'columns_1', 'width' => 'w960', 'height' => 'h640', 'title' => 'Выбор организационно правовой формы']],
        ]);
    }

    private function preparePagination($itemsTotal, $searchUri = null) {
        $currentPage = (int)$this->request->get('page');

        $pagesTotal = ceil($itemsTotal / self::ITEMS_PER_PAGE);

        $pagesTotal = $pagesTotal < 1 ? 1 : $pagesTotal;

        $currentPage = $currentPage < 1 ? 1 : $currentPage;
        $currentPage = $currentPage > $pagesTotal ? $pagesTotal : $currentPage;

        $startEnabled = 0;
        $endEnabled = 0;

        $leftSide = [];
        $rightSide = [];

        if ($pagesTotal > 1) {
            $leftPage  = $currentPage - self::PAGE_LEFT_RIGHT_DELTA;
            $rightPage = $currentPage + self::PAGE_LEFT_RIGHT_DELTA;

            $startEnabled = $leftPage > 1;
            $endEnabled = $rightPage < $pagesTotal;

            for($a = 0, $len = self::PAGE_LEFT_RIGHT_DELTA; $a < $len; $a++) {
                $leftPageNumber = $currentPage - ($len - $a);
                $rightPageNumber = $currentPage + ($a + 1);

                if ($leftPageNumber >= 1) {
                    $leftSide[] = $leftPageNumber;
                }

                if ($rightPageNumber <= $pagesTotal) {
                    $rightSide[] = $rightPageNumber;
                }
            }
        }

        $paginationData = [
            'items_total' => $itemsTotal,
            'start_enabled' => $startEnabled,
            'end_enabled' => $endEnabled,
            'left_side' => $leftSide,
            'right_side' => $rightSide,
            'current_page' => $currentPage,
            'pages_total' => $pagesTotal,
            'pagination_enabled' =>  $pagesTotal > 1,
            'pagination_uri' => $searchUri ? $searchUri.'&page=' : '?page=',
            'sql_offset' => $currentPage == 1 ? '' : ' OFFSET '.(($currentPage - 1) * self::ITEMS_PER_PAGE)
        ];

        $this->view->setVar('searchEnginePagination', $paginationData);

        return $paginationData;
    }

    private function prepareSorting(&$sortingMap) {
        $allowedOrders = [
            'asc' => 1,
            'desc' => 1
        ];

        $sortingString = mb_strtolower(trim($this->request->get('sorting')));

        $orderList = [];

        if ($sortingString != '') {
            $sortParts = explode(',',$sortingString);

            $isCleared = 0;

            for($a = 0, $len = sizeof($sortParts); $a < $len; $a++) {
                $orderParts = explode('-', $sortParts[$a]);

                if (sizeof($orderParts) != 2) {
                    continue;
                }

                if (!(isset($sortingMap[$orderParts[0]]) &&
                    isset($allowedOrders[$orderParts[1]]) &&
                    $sortingMap[$orderParts[0]]['sql'])) {
                    continue;
                }

                if (!$isCleared) {
                    $orderList = [];
                    $isCleared = 1;
                }

                $orderList[] = $sortingMap[$orderParts[0]]['sql'].' '.$orderParts[1];

                $sortingMap[$orderParts[0]]['selected'] = 1;
                $sortingMap[$orderParts[0]]['order'] = $a + 1;
                $sortingMap[$orderParts[0]]['mode'] = $orderParts[1];
            }
        }

        return sizeof($orderList) ? implode(',', $orderList) : false;
    }

    private function prepareColumns(&$columnsMap) {
        $columnsString = mb_strtolower(trim($this->request->get('columns')));

        if ($columnsString == '') {
            return;
        }

        $columns = explode(',',$columnsString);

        foreach($columnsMap as $column => $data) {
            $columnsMap[$column]['active'] = 0;
        }

        for($a = 0, $len = sizeof($columns); $a < $len; $a++) {
            $column = $columns[$a];

            if (!isset($columnsMap[$column])) {
                continue;
            }

            $columnsMap[$column]['order'] = $a;
            $columnsMap[$column]['active'] = 1;
        }
    }

    public function addAction()
    {
        if ($this->request->isPost()) {
            $this->counterAgentCreate();
        }

        $this->view->setVar('mainMenuData', [
            'active' => 'pm_menu_counteragents',
            'overlay' => 1,
            'template' => 'counteragents/menu_add',
            'template_sticky' => 'counteragents/menu_add_sticky',
            'icon' => 'users.svg',
            'icon_sub' => 'plus.svg',
            'title' => 'Добавление контрагента',
        ]);

        $this->hapi->setHapiAction('add');



    }

    private function counterAgentCreate() {
        $title = mb_strtoupper(trim($this->request->getPost('title')));
        $daData = json_encode(json_decode($this->request->getPost('dadata'), true));

        $result = (int)$this->db->query(
            'SELECT pm.counteragent_create(:mid, :tit::varchar(512), :ddt::jsonb) AS res',
            [
                'mid' => $this->user->getId(),
                'tit' => $title,
                'ddt' => $daData
            ]
        )->fetch()['res'];

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);
    }

    public function agentAction($counterAgentId = 0, $section = '')
    {
        if ($this->request->isPost()) {
            $act = $this->request->get('act');

            if ($act == 'agent_edit_title') {
                $this->agentEditTitle($counterAgentId);
            }
            else if ($act == 'agent_edit_tax') {
                $this->agentEditTax($counterAgentId);
            }
            else if ($act == 'agent_edit_comment') {
                $this->agentEditComment($counterAgentId);
            }
            else if ($act == 'agent_edit_stamp') {
                $this->agentEditStamp($counterAgentId);
            }
            else if ($act == 'agent_edit_manager') {
                $this->agentEditManager($counterAgentId);
            }
            else if ($act == 'segment_link') {
                $this->segmentUpdate($counterAgentId, 'link');
            }
            else if ($act == 'segment_unlink') {
                $this->segmentUpdate($counterAgentId, 'unlink');
            }
            else if ($act == 'technology_link') {
                $this->technologyUpdate($counterAgentId, 'link');
            }
            else if ($act == 'technology_unlink') {
                $this->technologyUpdate($counterAgentId, 'unlink');
            }
            else if ($act == 'network_add_link') {
                $this->networkAddLink($counterAgentId);
            }
            else if ($act == 'network_add_file') {
                $this->networkAddFile($counterAgentId);
            }
            else if ($act == 'network_delete') {
                $this->networkDelete($counterAgentId);
            }
            else if ($act == 'network_pin_toggle') {
                $this->networkPinToggle($counterAgentId);
            }
        }


        $counterAgentRow = $this->db->query(
            '
                SELECT c.counteragent_id
                     , c.counteragent_title
                     , c.counteragent_comment_commerce
                     , c.counteragent_comment_technical
                     , c.create_member_id
                     , c.update_member_id
                     , c.manager_member_id
                     , c.file_node_id
                     , m1.member_nick AS create_member_nick
                     , m2.member_nick AS update_member_nick
                     , m3.member_nick AS manager_member_nick
                     , ec.external_counteragent_id AS sd_customer_id
                     , c.counteragent_create_date::timestamp(0) AS counteragent_create_date
                     , c.counteragent_update_date::timestamp(0) AS counteragent_update_date
                     , c.contact_node_id
                     , tt.tax_type_id
                     , tt.type_title AS tax_title
                     , c.counteragent_stamp_id
                     , cs.stamp_title
                FROM pm.counteragent AS c
                     LEFT JOIN integration.external_counteragent AS ec 
                          ON   ec.external_id = 1 
                           AND ec.counteragent_id = c.counteragent_id
                   , pm.tax_type AS tt
                   , pm.counteragent_stamp AS cs
                   , public.members AS m1
                   , public.members AS m2
                   , public.members AS m3
                     
                WHERE c.counteragent_id = :cid
                  AND tt.tax_type_id = c.tax_type_id
                  AND cs.counteragent_stamp_id = c.counteragent_stamp_id
                  AND m1.member_id = c.create_member_id
                  AND m3.member_id = c.manager_member_id
            ',
            [
                'cid' => $counterAgentId
            ]
        )->fetch();

        $taxes = $this->db->query(
            '
                SELECT tt.tax_type_id
                     , tt.type_title
                FROM pm.tax_type AS tt
                ORDER BY tt.type_title
            '
        );

        $taxesOptions = [];

        while($row = $taxes->fetch()) {
            $taxesOptions[] = [$row['tax_type_id'], $row['type_title']];
        }

        $stamps = $this->db->query(
            '
                SELECT cs.counteragent_stamp_id
                     , cs.stamp_title
                FROM pm.counteragent_stamp AS cs
                ORDER BY cs.stamp_title
            '
        );

        $stampsOptions = [];

        while($row = $stamps->fetch()) {
            $stampsOptions[] = [$row['counteragent_stamp_id'], $row['stamp_title']];
        }

        $this->view->setLayout('counteragent');

        $this->view->setVar('taxesOptions', $taxesOptions);
        $this->view->setVar('stampsOptions', $stampsOptions);
        $this->view->setVar('counterAgentRow', $counterAgentRow);

        $this->view->setVar('mainMenuData', [
            'active' => 'pm_menu_counteragents',
            'overlay' => 1,
            'template' => 'counteragents/menu_agent',
            'icon' => 'users.svg',
            'title' => 'Контрагент',
        ]);

        $this->hapi->setHapiAction('agent');

        $sectionsList = [
            ['uri' => '',          'icon' => 'notification', 'selected' => '', 'title' => 'Общее'],
            ['uri' => 'orders',    'icon' => 'basket',       'selected' => '', 'title' => 'Заказы'],
            ['uri' => 'contracts', 'icon' => 'rules',        'selected' => '', 'title' => 'Договора'],
            ['uri' => 'services',  'icon' => 'archive',      'selected' => '', 'title' => 'Услуги'],
            [
                'icon' => 'blend',
                'active' => 0,
                'title' => 'Сеть партнера',
                'sections' => [
                    ['uri' => 'networks',     'icon' => 'link',   'selected' => '', 'title' => 'Ссылки и файлы'],
                    ['uri' => 'segments',     'icon' => 'earth',  'selected' => '', 'title' => 'Сегменты сети'],
                    ['uri' => 'technologies', 'icon' => 'router', 'selected' => '', 'title' => 'Технологии предоставления']
                ]
            ],
            ['uri' => 'addresses', 'icon' => 'location1',    'selected' => '', 'title' => 'Адреса предосталения услуг'],
            ['uri' => 'contacts',  'icon' => 'users1',       'selected' => '', 'title' => 'Контакты'],
            ['uri' => 'documents', 'icon' => 'sharedfile',   'selected' => '', 'title' => 'Документы'],
            ['uri' => 'history',   'icon' => 'server',       'selected' => '', 'title' => 'История']
        ];

        for ($a = 0, $len = sizeof($sectionsList); $a < $len; $a++) {
            $sec = $sectionsList[$a];

            if (isset($sec['uri']) && $sec['uri'] == $section) {
                $sectionsList[$a]['selected'] = 'sel';
                continue;
            }

            if (isset($sec['sections'])) {
                for($b = 0, $lenB = sizeof($sec['sections']); $b < $lenB; $b++) {
                    $subSec = $sec['sections'][$b];

                    if ($subSec['uri'] == $section) {
                        $sectionsList[$a]['sections'][$b]['selected'] = 'sel';
                        $sectionsList[$a]['active'] = 1;
                    }
                }
            }
        }

        $this->view->setVar('counterAgentId', $counterAgentId);
        $this->view->setVar('counterAgentSectionsList', $sectionsList);

        if ($section == '') {
            $this->agentSectionAgent($counterAgentId, $counterAgentRow);
        }
        else if ($section == 'networks') {
            $this->agentSectionNetworks($counterAgentId);
        }
        else if ($section == 'segments') {
            $this->agentSectionSegments($counterAgentId);
        }
        else if ($section == 'technologies') {
            $this->agentSectionTechnologies($counterAgentId);
        }
        else if ($section == 'contacts') {
            $this->agentSectionContacts($counterAgentRow['contact_node_id']);
        }
        else if ($section == 'documents') {
            $this->agentSectionDocuments($counterAgentRow);
        }
        else if ($section == 'contracts') {
            $this->agentSectionContracts($counterAgentRow);
        }
        else {
            $this->view->setVar(
                'mainPlaceHolderRight',
                [
                    'icon' => 'hammer-wrench',
                    'title' => 'Раздел находится в разработке'
                ]
            );

            $this->view->pick('partials/placeholders/right_side');
        }
    }

    private function agentSectionAgent($counterAgentId, $counterAgentRow)
    {
        $this->hapi->setHapiAction('agent');

        $this->view->setVar('counterAgentAddresses', $this->db->query(
            '
                SELECT can.address_node_id
                     , adt.address_type_id 
                     , adt.type_title 
                     , adt.type_title 
                     , COALESCE(a.address_id, 0) AS address_id
                     , COALESCE(adp.part_value || COALESCE(NULLIF(\', \' || TRIM(
                         COALESCE(NULLIF(\' пд \' || a.address_entrance, \' пд \'), \'\') ||
                         COALESCE(NULLIF(\' эт \' || a.address_floor,    \' эт \'), \'\') ||
                         COALESCE(NULLIF(\' кв \' || a.address_flat,     \' кв \'), \'\')
                       ), \', \'), \'\'), \'\') AS address_full
                FROM pm.counteragent_address_node AS can
                     CROSS JOIN pm.address_node AS an
                     CROSS JOIN pm.address_node_type_allow_type AS antat 
                     CROSS JOIN pm.address_type AS adt
                     LEFT JOIN pm.address_node_address AS ana 
                          ON   ana.address_node_id = an.address_node_id
                           AND ana.address_type_id = antat.address_type_id
                     LEFT JOIN pm.address AS a ON a.address_id = ana.address_id
                     LEFT JOIN pm.address_dict_part AS adp 
                          ON   adp.address_dict_id = a.address_dict_id
                           AND adp.address_scope_id = 13
                WHERE can.counteragent_id = :cid
                  AND an.address_node_id = can.address_node_id
                  AND an.address_node_type_id = 1
                  AND antat.address_node_type_id = an.address_node_type_id  
                  AND adt.address_type_id = antat.address_type_id
                ORDER BY adt.type_order
            ',
            [
                'cid' => $counterAgentId
            ]
        )->fetchAll());

        $this->view->setVar('counterAgentRequisites', json_decode($this->db->query(
            '
                SELECT cr.requisite_dadata_payload
                FROM pm.counteragent_requisite AS cr
                WHERE cr.counteragent_id = :cid
                  AND cr.requisite_active = 1  
            ',
            [
                'cid' => $counterAgentId
            ]
        )->fetch()['requisite_dadata_payload'], true));

        $this->view->setVar(
            'networksList',
            $this->db->query(
                '
                    SELECT cnl.counteragent_network_id
                         , cnl.counteragent_network_type_id
                         , cnl.type_title
                         , cnl.type_icon
                         , cnl.network_title
                         , cnl.network_address
                         , cnl.network_create_date
                         , cnl.network_pin
                         , 1 AS hide_controls
                    FROM pm.counteragent_network_list AS cnl
                    WHERE cnl.counteragent_id = :cid   
                      AND cnl.network_pin = 1  
                ',
                [
                    'cid' => $counterAgentId
                ]
            )->fetchAll()
        );

        $this->view->setVar(
            'contactsList',
            (new ContactsHandler($counterAgentRow['contact_node_id']))->getContacts([
                'filter_node_traits' => [2]
            ])
        );

        $this->view->pick('counteragents/agent');
    }

    private function agentSectionNetworks($counterAgentId)
    {
        $this->hapi->setHapiAction('agent_networks');

        $this->view->setVar(
            'networksList',
            $this->db->query(
                '
                    SELECT cnl.counteragent_network_id
                         , cnl.counteragent_network_type_id
                         , cnl.type_title
                         , cnl.type_icon
                         , cnl.network_title
                         , cnl.network_address
                         , cnl.network_create_date
                         , cnl.network_pin
                    FROM pm.counteragent_network_list AS cnl
                    WHERE cnl.counteragent_id = :cid                
                ',
                [
                    'cid' => $counterAgentId
                ]
            )->fetchAll()
        );

        $this->view->pick('counteragents/networks');
        $this->view->setVar('mainSectionHeaderRightTemplate', 'counteragents/header_right_networks');
    }

    private function agentSectionSegments($counterAgentId)
    {
        $this->hapi->setHapiAction('agent_segments');

        $segments = $this->db->query(
            '
                SELECT isg.international_segment_id
                     , isg.parent_international_segment_id
                     , isg.segment_title
                     , CASE WHEN cis.international_segment_id IS NULL THEN 0 ELSE 1 END AS segment_selected
                FROM pm.international_segment AS isg
                     LEFT JOIN pm.counteragent_international_segment AS cis 
                          ON   cis.international_segment_id = isg.international_segment_id
                           AND cis.counteragent_id = :cid
                ORDER BY isg.segment_title
            ',
            [
                'cid' => $counterAgentId
            ]
        );

        $segmentsMap = [];

        while ($row = $segments->fetch()) {
            $parentId = (int)$row['parent_international_segment_id'];

            $row['can_select'] = 1;

            $segmentsMap[$parentId] = $segmentsMap[$parentId] ?? [];

            $segmentsMap[$parentId][] = $row;
        }

        $this->view->setVar('internationalSegmentsMap', $segmentsMap);
        $this->view->pick('counteragents/segments');
    }

    private function agentSectionTechnologies($counterAgentId)
    {
        $this->hapi->setHapiAction('agent_technologies');


        $types = $this->db->query(
            '
                SELECT ptt.provide_technology_type_id
                     , ptt.type_title
                FROM pm.provide_technology_type AS ptt
                ORDER BY ptt.type_title
            '
        );

        $typesMap = [];

        while ($row = $types->fetch()) {
            $typesMap[$row['provide_technology_type_id']] = [
                'provide_technology_type_id' => $row['provide_technology_type_id'],
                'type_title' => $row['type_title'],
                'counts' => [
                ],
                'list' => []
            ];
        }

        $technologies = $this->db->query(
            '
               SELECT pt.provide_technology_id
                    , pt.provide_technology_type_id
                    , pt.technology_title
                    , CASE WHEN cpt.provide_technology_id IS NULL THEN 0 ELSE 1 END AS technology_selected
               FROM pm.provide_technology AS pt
                    LEFT JOIN pm.counteragent_provide_technology AS cpt 
                         ON   cpt.provide_technology_id = pt.provide_technology_id
                          AND cpt.counteragent_id = :cid
               ORDER BY pt.technology_title
            ',
            [
                'cid' => $counterAgentId
            ]
        );

        while ($row = $technologies->fetch()) {
            $row['can_select'] = 1;

            $typesMap[$row['provide_technology_type_id']]['list'][] = $row;
        }

        $this->view->setVar('technologyTypesMap', $typesMap);
        $this->view->pick('counteragents/technologies');
    }

    private function agentSectionContacts($contactNodeId)
    {
        $this->hapi->setHapiAction('agent_contacts');

        $this->view->setVar('contactsList', (new ContactsHandler($contactNodeId))->getContacts());

        /*echo '<pre>';
        print_r($this->view->getVar('contactsList'));
        exit;*/


        $this->view->pick('counteragents/contacts');
        $this->view->setVar('mainSectionHeaderRightTemplate', 'counteragents/header_right_contacts');
    }

    private function agentSectionDocuments($counterAgentRow)
    {
        $files = $this->db->query(
            '
                WITH nodes AS (
                        SELECT '.$counterAgentRow['file_node_id'].' AS file_node_id
                             , \'counteragent\' AS entity_type
                             , '.$counterAgentRow['counteragent_id'].' AS entity_id
                             , :name AS entity_title
                             , 1 AS entity_order
                             , \'Контрагент\' AS entity_type_title
                        UNION
                        SELECT c.file_node_id
                             , \'contact\' AS entity_type
                             , c.contact_id AS entity_id
                             , c.contact_nickname AS entity_title     
                             , 2 AS entity_order 
                             , \'Контакты\' AS entity_type_title
                        FROM pm.contact_node_contact AS cnc
                           , pm.contact AS c
                        WHERE cnc.contact_node_id = :cid
                          AND c.contact_id = cnc.contact_id 
                     )
                SELECT n.entity_id
                     , n.entity_type
                     , n.entity_title
                     , n.entity_type_title
                     , fl.file_id
                     , fl.file_name
                     , fl.file_hash
                     , fl.file_extension
                     , fl.file_create_date
                     , fl.type_title
                     , fl.member_id
                     , fl.member_nick
                     , fl.file_trait
                FROM nodes AS n    
                   , common.file_list AS fl
                WHERE fl.file_node_id = n.file_node_id 
                ORDER BY n.entity_order
                       , fl.type_title
                       , fl.file_create_date DESC
            ',
            [
                'name' => $counterAgentRow['counteragent_title'],
                'cid' => $counterAgentRow['contact_node_id']
            ]
        );

        $filesMap = [];

        while($row = $files->fetch()) {
            $filesMap[$row['entity_type']] = $filesMap[$row['entity_type']] ?? [
                'title' => $row['entity_type_title'],
                'list' => []
            ];

            $filesMap[$row['entity_type']]['list'][] = $row;
        }

        $this->view->pick('counteragents/documents');
        $this->view->setVar('documentsMap', $filesMap);
    }

    private function agentSectionContracts($counterAgentRow)
    {
        $contractsList = (new ContractsHandler())->counterAgentContracts((int)$counterAgentRow['counteragent_id']);

        $this->view->pick('counteragents/contracts');
        $this->view->setVar('mainSectionHeaderRightTemplate', 'counteragents/header_right_contracts');
        $this->view->setVar('contractsList', $contractsList);
    }

    private function networkAddLink($counterAgentId)
    {
        $url = trim(htmlentities($this->request->getPost('url')));
        $title = trim(htmlentities($this->request->getPost('title')));

        $html = '';
        $status = 'yes';
        $result = 0;
        $notification = 'Ссылка добавлена';

        if ($url == '' || $title == '') {
            $status = 'no';
            $notification = 'Необходимо указать название и ссылку';
        }
        else {
            $result = (int)$this->db->query(
                'SELECT pm.counteragent_network_create(:cid, :mid, :tid, :tit, :url) AS res',
                [
                    'tid' => 1,
                    'mid' => $this->user->getId(),
                    'cid' => $counterAgentId,
                    'url' => $url,
                    'tit' => $title,
                ]
            )->fetch()['res'];

            $html = $this->view->getPartial('counteragents/partials/network_item', $this->db->query(
                '
                    SELECT cnl.counteragent_network_id
                         , cnl.counteragent_network_type_id
                         , cnl.type_title
                         , cnl.type_icon
                         , cnl.network_title
                         , cnl.network_address
                         , cnl.network_create_date
                    FROM pm.counteragent_network_list AS cnl
                    WHERE cnl.counteragent_network_id = :nid
                ',
                [
                    'nid' => $result
                ]
            )->fetch());
        }


        $this->sendResponseAjax([
            'state' => $status,
            'notification' => $notification,
            'html' => $html,
            'result' => $result
        ]);
    }

    private function networkAddFile($counterAgentId)
    {
        $title = trim(htmlentities($this->request->getPost('title')));

        $html = '';
        $status = 'yes';
        $result = 0;
        $notification = 'Файл добавлен';

        if ($_FILES['file']['error'] != 0 || $title == '') {
            $status = 'no';
            $notification = 'Необходимо указать название и загрузить файл';
        }
        else {
            $file = $_FILES['file'];
            $expl = explode('.', $file['name']);

            $type = $expl[sizeof($expl) - 1];

            $fileName = hash('crc32b', $file['tmp_name']).'.'.$type;

            move_uploaded_file($file['tmp_name'], DIR_PUBLIC.'uploads/networks/'.$fileName);

            $result = (int)$this->db->query(
                'SELECT pm.counteragent_network_create(:cid, :mid, :tid, :tit, :url) AS res',
                [
                    'tid' => 2,
                    'mid' => $this->user->getId(),
                    'cid' => $counterAgentId,
                    'url' => $fileName,
                    'tit' => $title,
                ]
            )->fetch()['res'];

            $html = $this->view->getPartial('counteragents/partials/network_item', $this->db->query(
                '
                    SELECT cnl.counteragent_network_id
                         , cnl.counteragent_network_type_id
                         , cnl.type_title
                         , cnl.type_icon
                         , cnl.network_title
                         , cnl.network_address
                         , cnl.network_create_date
                    FROM pm.counteragent_network_list AS cnl
                    WHERE cnl.counteragent_network_id = :nid
                ',
                [
                    'nid' => $result
                ]
            )->fetch());
        }

        $this->sendResponseAjax([
            'state' => $status,
            'notification' => $notification,
            'html' => $html,
            'result' => $result
        ]);
    }

    private function networkDelete($counterAgentId) {
        $networkId = (int)$this->request->getPost('counteragent_network_id');

        $result = (int)$this->db->query(
            'SELECT pm.counteragent_network_delete(:cid, :nid, :mid) AS res',
            [
                'cid' => $counterAgentId,
                'nid' => $networkId,
                'mid' => $this->user->getId()
            ]
        )->fetch()['res'];

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);
    }

    private function networkPinToggle($counterAgentId) {
        $networkId = (int)$this->request->getPost('counteragent_network_id');

        $result = (int)$this->db->query(
            'SELECT pm.counteragent_network_pin(:cid, :nid, :mid) AS res',
            [
                'cid' => $counterAgentId,
                'nid' => $networkId,
                'mid' => $this->user->getId()
            ]
        )->fetch()['res'];

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);
    }

    private function agentEditTitle($counterAgentId)
    {
        $title = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.counteragent_update_title(:cid, :mid, :tit) AS res',
            [
                'cid' => $counterAgentId,
                'mid' => $this->user->getId(),
                'tit' => $title,
            ]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Псевдоним не может быть пустым',
                    -2 => 'Псевдоним уже занят'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result,
            'result_content' => htmlspecialchars($title),
            'notification' => 'Данные сохранены'
        ]);
    }

    private function agentEditTax($counterAgentId)
    {
        $taxTypeId = (int)$this->request->getPost('tax_type');

        $result = (int)$this->db->query(
            'SELECT pm.counteragent_update_tax_type(:cid, :mid, :tit) AS res',
            [
                'cid' => $counterAgentId,
                'mid' => $this->user->getId(),
                'tit' => $taxTypeId,
            ]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => 'Что-то пошло не так'
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result,
            'result_content' => htmlspecialchars($this->db->query(
                'SELECT type_title FROM pm.tax_type WHERE tax_type_id = :tid',
                ['tid' => $taxTypeId]
            )->fetch()['type_title']),
            'notification' => 'Данные сохранены'
        ]);
    }

    private function agentEditComment($counterAgentId)
    {
        $comment = trim($this->request->getPost('comment'));
        $type = mb_strtolower(trim($this->request->get('type'))) == 'technical' ? 'technical' : 'commerce';

        $result = (int)$this->db->query(
            'SELECT pm.counteragent_update_comment_'.$type.'(:cid, :mid, :cmn) AS res',
            [
                'cid' => $counterAgentId,
                'mid' => $this->user->getId(),
                'cmn' => $comment,
            ]
        )->fetch()['res'];

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result,
            'result_content' => $comment ? nl2br(htmlspecialchars($comment)) : 'Комментарий отсутствует',
            'notification' => 'Данные сохранены'
        ]);
    }

    private function agentEditStamp($counterAgentId)
    {
        $counteragentStampId = (int)$this->request->getPost('counteragent_stamp');

        $stampFile = $_FILES['counteragent_stamp_file'];

        if (!$stampFile['error'] && $counteragentStampId == 2) {
            $fileHandler = new FilesHandler((int)$this->db->query(
                'SELECT file_node_id FROM pm.counteragent WHERE counteragent_id = :cid',
                ['cid' => $counterAgentId]
            )->fetch()['file_node_id']);

            $fileId = $fileHandler->addFile(2, $stampFile);

            $fileHandler->linkSingleTrait($fileId, 2);
        }

        $result = (int)$this->db->query(
            '
                SELECT pm.counteragent_update_stamp(:cid, :mid, :sid) AS res
            ',
            [
                'cid' => $counterAgentId,
                'mid' => $this->user->getId(),
                'sid' => $counteragentStampId
            ]
        )->fetch()['res'];

        $this->sendResponseAjax([
            'state' => $result == 0 ? 'yes' : 'no',
            'result' => $result,
            'result_content' => htmlspecialchars($this->db->query(
                'SELECT stamp_title FROM pm.counteragent_stamp WHERE counteragent_stamp_id = :sid',
                ['sid' => $counteragentStampId]
            )->fetch()['stamp_title'])
        ]);
    }

    private function agentEditManager($counterAgentId)
    {
        $managerId = (int)$this->request->getPost('manager_id');

        $result = (int)$this->db->query(
            'SELECT pm.counteragent_update_manager(:cid, :mid, :rid) AS res',
            [
                'cid' => $counterAgentId,
                'mid' => $this->user->getId(),
                'rid' => $managerId,
            ]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => 'Что-то пошло не так'
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result,
            'result_content' => htmlspecialchars(trim($this->request->getPost('manager'))),
            'notification' => 'Данные сохранены'
        ]);
    }

    private function segmentUpdate($counterAgentId, $type)
    {
        $segmentId = (int)$this->request->getPost('segment_id');
        $childs  = json_decode($this->request->getPost('childs'), true);
        $parents = json_decode($this->request->getPost('parents'), true);

        $result = (int)$this->db->query(
            'SELECT pm.counteragent_international_segment_'.$type.'(:cid, :sid) AS res',
            [
                'cid' => $counterAgentId,
                'sid' => $segmentId
            ]
        )->fetch()['res'];

        $this->db->begin();

        for($a = 0, $len = sizeof($childs); $a < $len; $a++) {
            $this->db->query(
                'SELECT pm.counteragent_international_segment_'.$type.'(:cid, :sid) AS res',
                [
                    'cid' => $counterAgentId,
                    'sid' => (int)$childs[$a]
                ]
            )->fetch();
        }

        if ($type == 'link') {
            for($a = 0, $len = sizeof($parents); $a < $len; $a++) {
                $this->db->query(
                    'SELECT pm.counteragent_international_segment_link(:cid, :sid) AS res',
                    [
                        'cid' => $counterAgentId,
                        'sid' => (int)$parents[$a]
                    ]
                )->fetch();
            }
        }

        $this->db->commit();

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);
    }

    private function technologyUpdate($counterAgentId, $type)
    {
        $technologyId = (int)$this->request->getPost('technology_id');

        $result = (int)$this->db->query(
            'SELECT pm.counteragent_provide_technology_'.$type.'(:cid, :sid) AS res',
            [
                'cid' => $counterAgentId,
                'sid' => $technologyId
            ]
        )->fetch()['res'];

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);
    }

    private function searchEngine()
    {
        $filterMap = [];
        $requestUri = [];

        $columnsMap = [
            'id' => [
                'name' => 'id',
                'column' => 'id',
                'title' => 'ID',
                'order' => 0,
                'active' => 1,
                'sql' => 'counteragent_id'
            ],
            'title' => [
                'name' => 'title',
                'column' => 'counteragent',
                'title' => 'Контрагент',
                'order' => 1,
                'active' => 1,
                'sql' => 'counteragent_title'
            ],
            'resident' => [
                'name' => 'resident',
                'column' => 'base',
                'title' => 'Резидент',
                'order' => 2,
                'active' => 1,
                'sql' => 'resident_status'
            ],
            'opf' => [
                'name' => 'opf',
                'column' => 'base',
                'title' => 'ОПФ',
                'order' => 3,
                'active' => 1,
                'sql' => 'opf_name_short'
            ],
            'inn' => [
                'name' => 'inn',
                'column' => 'base',
                'title' => 'ИНН',
                'order' => 4,
                'active' => 1,
                'sql' => 'requisite_inn'
            ],
            'kpp' => [
                'name' => 'kpp',
                'column' => 'base',
                'title' => 'КПП',
                'order' => 5,
                'active' => 1,
                'sql' => 'requisite_kpp'
            ],
            'manager' => [
                'name' => 'manager',
                'column' => 'base',
                'title' => 'Менеджер',
                'order' => 6,
                'active' => 1,
                'sql' => 'member_nick'
            ],
            'department' => [
                'name' => 'department',
                'column' => 'base',
                'title' => 'Отдел',
                'order' => 7,
                'active' => 1,
                'sql' => 'group_title'
            ],
            'updated' => [
                'name' => 'updated',
                'column' => 'base',
                'title' => 'Обновление',
                'order' => 8,
                'active' => 0,
                'sql' => 'counteragent_update_date'
            ]
        ];

        $sortingMap = [
            'id' => [
                'name' => 'id',
                'title' => 'ID',
                'order' => 100,
                'selected' => 0,
                'mode' => '',
                'sql' => 'c.counteragent_id'
            ],
            'title' => [
                'name' => 'title',
                'title' => 'Контрагент',
                'order' => 101,
                'selected' => 0,
                'mode' => '',
                'sql' => 'c.counteragent_title'
            ],
            'resident' => [
                'name' => 'resident',
                'title' => 'Резидент',
                'order' => 102,
                'selected' => 0,
                'mode' => '',
                'sql' => 'crs.resident_status'
            ],
            'opf' => [
                'name' => 'opf',
                'title' => 'ОПФ',
                'order' => 103,
                'selected' => 0,
                'mode' => '',
                'sql' => 'o.opf_name_short'
            ],
            'inn' => [
                'name' => 'inn',
                'title' => 'ИНН',
                'order' => 104,
                'selected' => 0,
                'mode' => '',
                'sql' => 'cr.requisite_inn'
            ],
            'kpp' => [
                'name' => 'kpp',
                'title' => 'КПП',
                'order' => 106,
                'selected' => 0,
                'mode' => '',
                'sql' => 'cr.requisite_kpp'
            ],
            'manager' => [
                'name' => 'manager',
                'title' => 'Менеджер',
                'order' => 107,
                'selected' => 0,
                'mode' => '',
                'sql' => 'm.member_nick'
            ],
            'department' => [
                'name' => 'department',
                'title' => 'Отдел',
                'order' => 109,
                'selected' => 0,
                'mode' => '',
                'sql' => 'gl.group_title'
            ],
            'updated' => [
                'name' => 'updated',
                'title' => 'Обновление',
                'order' => 110,
                'selected' => 0,
                'mode' => '',
                'sql' => 'c.counteragent_update_date'
            ]
        ];

        $stmtSelect = [
            'c.counteragent_id',
            'c.counteragent_title',
            'o.opf_id',
            'o.opf_name_short',
            'cr.requisite_inn',
            'cr.requisite_kpp',
            '\'Да\' AS resident_status',
            'm.member_id',
            'm.member_gender',
            'm.member_nick',
            'gl.group_id',
            'gl.group_title',
            'c.counteragent_update_date::date AS counteragent_update_date'
        ];

        $stmtFrom = [
            'pm.counteragent AS c',
            'pm.counteragent_requisite AS cr',
            'pm.counteragent_resident AS crs',
            'pm.counteragent_status AS cs',
            'pm.opf AS o',
            'public.members AS m',
        ];

        $stmtWhere = [
            'cr.counteragent_id = c.counteragent_id',
            'cr.requisite_active = 1',
            'crs.counteragent_resident_id = c.counteragent_resident_id',
            'cs.counteragent_status_id = c.counteragent_status_id',
            'o.opf_id = c.opf_id',
            'm.member_id = c.manager_member_id',
        ];

        ////////////////////////
        $filterResident = explode('_', $this->request->get('resident'));

        if (sizeof($filterResident) == 2 && $filterResident[0] == 'resident') {
            $residentId = (int)$filterResident[1];
            $stmtWhere[] = 'c.counteragent_resident_id = '.$residentId;

            $requestUri[] = 'resident=resident_'.$residentId;

            $filterMap['resident'] = $residentId;
        }

        $filterOnlyMine = (int)$this->request->get('only_mine');

        if ($filterOnlyMine) {
            $stmtWhere[] = 'c.manager_member_id = '.$this->user->getId();

            $requestUri[] = 'only_mine=1';
            $filterMap['only_mine'] = 1;
        }

        $filterStatus = trim($this->request->get('status'));

        if ($filterStatus) {
            $statuses = explode(',', $filterStatus);

            $statusesList = [];
            $statusesReq = [];
            $statusesMap = [];

            for($a = 0, $len = sizeof($statuses); $a < $len; $a++) {
                $status = preg_replace('/[^a-zA-Zа-яА-Я0-9_-]/mui', '', $statuses[$a]);

                if ($status) {
                    $statusesList[] = '\''.$status.'\'';
                    $statusesReq[] = $status;
                    $statusesMap[$status] = 1;
                }
            }

            if (sizeof($statusesList)) {
                $requestUri[] = 'status='.implode(',', $statusesReq);
                $stmtWhere[] = 'cs.status_name IN ('.implode(',', $statusesList).')';

                $filterMap['status'] = $statusesMap;
            }
        }

        $filterOpf = trim($this->request->get('opf'));

        if ($filterOpf) {
            $opfIdList = explode(',', $filterOpf);

            for ($a = 0, $len = sizeof($opfIdList); $a < $len; $a++) {
                $opfIdList[$a] = (int)$opfIdList[$a];
            }

            $requestUri[] = 'opf='.implode(',', $opfIdList);
            $stmtWhere[] = 'c.opf_id IN ('.implode(',', $opfIdList).')';

            $filterMap['opf'] = $opfIdList;
        }

        $filterManager = trim($this->request->get('manager'));

        if ($filterManager) {
            $managerIdList = explode(',', $filterManager);

            for ($a = 0, $len = sizeof($managerIdList); $a < $len; $a++) {
                $managerIdList[$a] = (int)$managerIdList[$a];
            }

            $requestUri[] = 'manager='.implode(',', $managerIdList);
            $stmtWhere[] = 'c.manager_member_id IN ('.implode(',', $managerIdList).')';

            $filterMap['manager'] = $managerIdList;
        }

        $filterGroup = trim($this->request->get('group'));

        if ($filterGroup) {
            $groupIdList = explode(',', $filterGroup);

            for ($a = 0, $len = sizeof($groupIdList); $a < $len; $a++) {
                $groupIdList[$a] = (int)$groupIdList[$a];
            }

            $requestUri[] = 'group='.implode(',', $groupIdList);
            $stmtWhere[] = 'm.member_group IN (SELECT grl.group_id 
                                               FROM public.groups_relationships AS grl 
                                               WHERE grl.parent_id IN ('.implode(',', $groupIdList).')
                                              )';

            $filterMap['group'] = $groupIdList;
        }

        $filterSegment = trim($this->request->get('segment'));

        if ($filterSegment) {
            $segmentIdList = explode(',', $filterSegment);

            for ($a = 0, $len = sizeof($segmentIdList); $a < $len; $a++) {
                $segmentIdList[$a] = (int)$segmentIdList[$a];
            }

            $requestUri[] = 'segment='.implode(',', $segmentIdList);
            $stmtWhere[] = 'c.counteragent_id IN (SELECT cis.counteragent_id 
                                               FROM pm.counteragent_international_segment AS cis
                                                  , pm.international_segment_tree AS ist 
                                               WHERE ist.parent_international_segment_id IN ('.implode(',', $segmentIdList).')
                                                 AND cis.international_segment_id = ist.international_segment_id
                                              )';

            $filterMap['segment'] = $segmentIdList;
        }

        $filterTechnology = trim($this->request->get('technology'));

        if ($filterTechnology) {
            $technologyIdList = explode(',', $filterTechnology);

            for ($a = 0, $len = sizeof($technologyIdList); $a < $len; $a++) {
                $technologyIdList[$a] = (int)$technologyIdList[$a];
            }

            $requestUri[] = 'technology='.implode(',', $technologyIdList);
            $stmtWhere[] = 'c.counteragent_id IN (SELECT cpt.counteragent_id 
                                               FROM pm.counteragent_provide_technology AS cpt 
                                               WHERE cpt.provide_technology_id IN ('.implode(',', $technologyIdList).')
                                              )';

            $filterMap['technology'] = $technologyIdList;
        }

        $filterTechnologyCat = trim($this->request->get('technology_cat'));

        if ($filterTechnologyCat) {
            $technologyCatIdList = explode(',', $filterTechnologyCat);

            for ($a = 0, $len = sizeof($technologyCatIdList); $a < $len; $a++) {
                $technologyCatIdList[$a] = (int)$technologyCatIdList[$a];
            }

            $requestUri[] = 'technology_cat='.implode(',', $technologyCatIdList);
            $stmtWhere[] = 'c.counteragent_id IN (SELECT cpt.counteragent_id 
                                               FROM pm.provide_technology AS pt
                                                  , pm.counteragent_provide_technology AS cpt 
                                               WHERE pt.provide_technology_type_id IN ('.implode(',', $technologyCatIdList).')
                                                 AND cpt.provide_technology_id = pt.provide_technology_id
                                              )';

            $filterMap['technology_cat'] = $technologyCatIdList;
        }


        $title = trim($this->request->get('title'));

        if ($title) {

            $title = mb_strtolower(preg_replace('/[\'\"\;\:\=\(\)\`\!\#\@\\\\\/\<\>\,\.]/mui', '', $title));

            $stmtWhere[] = 'LOWER(c.counteragent_title) LIKE \'%'.$title.'%\'';

            $requestUri[] = 'title='.$title;
            $filterMap['title'] = $title;
        }
        
        $inn = trim($this->request->get('inn'));

        if ($inn) {
            $inn = preg_replace('/[^0-9]/mui', '', $inn);

            $stmtWhere[] = 'c.counteragent_id IN (SELECT cr1.counteragent_id 
                                                  FROM pm.counteragent_requisite AS cr1
                                                  WHERE cr1.requisite_inn LIKE \''.$inn.'%\'
                                                 )';

            $requestUri[] = 'inn='.$inn;
            $filterMap['inn'] = $inn;
        }

        $kpp = trim($this->request->get('kpp'));

        if ($kpp) {
            $kpp = preg_replace('/[^0-9]/mui', '', $kpp);

            $stmtWhere[] = 'c.counteragent_id IN (SELECT cr2.counteragent_id 
                                                  FROM pm.counteragent_requisite AS cr2
                                                  WHERE cr2.requisite_kpp LIKE \''.$kpp.'%\'
                                                 )';

            $requestUri[] = 'kpp='.$kpp;
            $filterMap['kpp'] = $kpp;
        }
        ///////////////////////



        $totalCount = (int)$this->db->query(
            '
                SELECT COUNT(*) AS total
                FROM '.implode(',', $stmtFrom).'
                WHERE '.implode(' AND ', $stmtWhere).'
            '
        )->fetch()['total'];

        $paginationData = $this->preparePagination($totalCount, sizeof($requestUri) ? '/counteragents?'.implode('&', $requestUri) : null);
        $sqlOffset = $paginationData['sql_offset'];

        unset($paginationData['sql_offset']);

        $sqlOrder = $this->prepareSorting($sortingMap);

        if ($sqlOrder) {
            $sqlOrder = 'ORDER BY '.$sqlOrder;
        }
        else {
            $sqlOrder = '';
        }

        $this->prepareColumns($columnsMap);

        /*echo '<pre>';
        print_r($sortingMap);
        exit;*/

        $stmtFrom[] = 'public.groups_langs AS gl';
        $stmtWhere[] = 'gl.group_id = m.member_group';
        $stmtWhere[] = 'gl.group_id = m.member_group';
        $stmtWhere[] = 'gl.lang_id = 1';

        $this->view->setVar('searchEngineContent', $this->db->query(
            '
                SELECT '.implode(',', $stmtSelect).'
                FROM '.implode(',', $stmtFrom).'
                WHERE '.implode(' AND ', $stmtWhere).'
                '.$sqlOrder.'
                LIMIT '.self::ITEMS_PER_PAGE.'
                '.$sqlOffset.'
            '
        )->fetchAll());



        $sortingList = $sortingMap;

        usort($sortingList, function ($item1, $item2) {
            return $item1['order'] <=> $item2['order'];
        });

        $columnsList = $columnsMap;

        usort($columnsList, function ($item1, $item2) {
            return $item1['order'] <=> $item2['order'];
        });

        $this->view->setVar('searchEngineColumns', $columnsList);
        $this->view->setVar('searchEngineSorting', $sortingMap);

        return [
            'filters' => $filterMap,
            'sorting' => $sortingMap,
            'columns' => $columnsMap,
            'sorting_list' => $sortingList,
            'columns_list' => $columnsList,
            'pagination' => $paginationData
        ];
    }

    protected function apiSearch_engine()
    {
        $act = $this->request->getPost('act');
        $subAct = $this->request->getPost('sub_act');
        $name = trim($this->request->getPost('name'));

        if ($act == 'search') {
            $tileUpdate = $this->request->getPost('tile_update');
            $result = $this->searchEngine();


            $filtersMap = $result['filters'];

            $tilesToUpdate = [];

            if ($tileUpdate == 'opf') {
                $opfTileList = $this->searchEngineOpfPrepareMenuTiles($filtersMap['opf'] ?? []);

                $tilesToUpdate['opf'] = '';

                for($a = 0, $len = sizeof($opfTileList); $a < $len; $a++) {
                    $tilesToUpdate['opf'] .= $this->view->getPartial(
                        'partials/eliza_search/tiles/text',
                        $opfTileList[$a]
                    );
                }
            }
            else if ($tileUpdate == 'manager') {
                $managerTileList = $this->searchEngineManagerPrepareMenuTiles($filtersMap['manager'] ?? []);

                $tilesToUpdate['manager'] = '';

                for($a = 0, $len = sizeof($managerTileList); $a < $len; $a++) {
                    $tilesToUpdate['manager'] .= $this->view->getPartial(
                        'partials/eliza_search/tiles/user',
                        $managerTileList[$a]
                    );
                }
            }
            else if ($tileUpdate == 'group') {
                $groupTileList = $this->searchEngineGroupPrepareMenuTiles($filtersMap['group'] ?? []);

                $tilesToUpdate['group'] = '';

                for($a = 0, $len = sizeof($groupTileList); $a < $len; $a++) {
                    $tilesToUpdate['group'] .= $this->view->getPartial(
                        'partials/eliza_search/tiles/text',
                        $groupTileList[$a]
                    );
                }
            }
            else if ($tileUpdate == 'segment') {
                $segmentTileList = $this->searchEngineSegmentPrepareMenuTiles($filtersMap['segment'] ?? []);

                $tilesToUpdate['segment'] = '';

                for($a = 0, $len = sizeof($segmentTileList); $a < $len; $a++) {
                    $tilesToUpdate['segment'] .= $this->view->getPartial(
                        'partials/eliza_search/tiles/text',
                        $segmentTileList[$a]
                    );
                }
            }
            else if ($tileUpdate == 'technology') {
                $technologyTileList = $this->searchEngineTechnologyPrepareMenuTiles(
                    $filtersMap['technology'] ?? [],
                    $filtersMap['technology_cat'] ?? []
                );

                $tilesToUpdate['technology'] = '';

                for($a = 0, $len = sizeof($technologyTileList); $a < $len; $a++) {
                    $tilesToUpdate['technology'] .= $this->view->getPartial(
                        'partials/eliza_search/tiles/text',
                        $technologyTileList[$a]
                    );
                }
            }

            $this->sendResponseAjax([
                'state' => 'yes',
                'content' => $this->view->getPartial('counteragents/partials/search_table'),
                'pagination' => $result['pagination'],
                'extend_tiles' => $tilesToUpdate
            ]);
        }
        else if ($act == 'extend') {
            if ($subAct == 'tiles') {
                if ($name == 'manager') {
                    $this->searchExtendManagerTiles();
                }
                else if ($name == 'group') {
                    $this->searchExtendGroupTiles();
                }
                else if ($name == 'segment') {
                    $this->searchExtendSegmentTiles();
                }
                else if ($name == 'technology') {
                    $this->searchExtendTechnologyTiles();
                }
                else if ($name == 'opf') {
                    $this->searchExtendOpfTiles();
                }
            }

        }
    }

    private function searchExtendManagerTiles() {
        $activeMap = $this->searchEngineParseActiveInt();

        $searchString = trim($this->request->getPost('search'));

        $binds = [];
        $where = [];


        if (sizeof($activeMap)) {
            $where[] = 'm.member_id IN ('.implode(',', $activeMap).')';
        }

        if ($searchString) {
            $where[] = 'm.member_nick_lower LIKE :like';
            $binds['like'] = '%'.mb_strtolower(trim($searchString)).'%';
        }

        $managers = $this->db->query(
            '
                SELECT res.member_id AS id
                     , res.member_gender AS gender
                     , res.member_nick AS nick
                     , ma.avatar_ver AS avatar
                     , res.counteragent_count AS counteragent_count
                FROM (
                         SELECT m.member_id
                              , m.member_nick
                              , m.member_gender
                              , COUNT(DISTINCT c.counteragent_id) AS counteragent_count
                         FROM pm.counteragent AS c
                            , public.members AS m
                         WHERE m.member_id = c.manager_member_id
                               '.(sizeof($where) ? ' AND ('.implode(' OR ', $where).')' : '').'
                         GROUP BY m.member_id
                                , m.member_nick
                                , m.member_gender
                     ) AS res
                     LEFT JOIN public.members_avatars AS ma ON ma.member_id = res.member_id
                ORDER BY res.member_nick
            ',
            $binds
        );

        $managersList = [];

        while($row = $managers->fetch()) {
            if (!(int)$row['avatar']) {
                $nick2 = mb_substr(preg_replace('/[^A-ZА-Я]/mu', '', $row['nick']), 0, 2);

                if(mb_strlen($nick2) < 1) {
                    $nick2 = mb_strtoupper(mb_substr(preg_replace('/[^a-zа-я]/mu', '', $row['nick']), 0, 2));
                }

                $row['nick2'] = $nick2;
            }

            $managersList[] = [
                'value' => $row['id'],
                'nick' => $row['nick'],
                'nick2' => $row['nick2'],
                'gender' => $row['gender'],
                'version' => $row['avatar'],
                'selected' => isset($activeMap[$row['id']]),
                'counteragent_count' => $row['counteragent_count']
            ];
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'html' => $this->view->getPartial('counteragents/search_engine/extend_manager_list', ['list' => $managersList])
        ]);
    }

    private function searchExtendGroupTiles() {
        $activeMap = $this->searchEngineParseActiveInt();

        $groups = $this->db->query(
            '
                WITH res AS (
                         SELECT gr.parent_id
                              , gl.group_title
                              , count(*) AS counteragent_count
                         FROM pm.counteragent AS c
                            , public.members AS m
                            , public.groups_relationships AS gr
                            , public.groups_langs AS gl
                         WHERE m.member_id = c.manager_member_id
                           AND gr.group_id = m.member_group
                           AND gl.group_id = gr.parent_id
                           AND gl.lang_id = 1
                         GROUP BY gr.parent_id
                                , gl.group_title
                     )
                SELECT g.group_id AS node_id
                     , COALESCE(g.group_parent_id, 0) AS parent_id
                     , res.group_title AS title
                     , res.counteragent_count
                FROM res
                   , public.groups AS g
                WHERE g.group_id = res.parent_id
                ORDER BY res.group_title            
            '
        );

        $groupsMap = [];

        while ($row = $groups->fetch()) {
            $parentId = (int)$row['parent_id'];

            $groupsMap[$parentId] = $groupsMap[$parentId] ?? [];

            $groupsMap[$parentId][] = [
                'value' => (int)$row['node_id'],
                'parent' => (int)$row['parent_id'],
                'title' => $row['title'],
                'descr' => $row['title'],
                'selected' => isset($activeMap[$row['node_id']]),
                'counts' => [
                    ['title' => 'Количество контрагентов: ', 'icon' => 'users', 'count' => $row['counteragent_count']]
                ]
            ];
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'html' => $this->view->getPartial('counteragents/search_engine/extend_tree_map', ['map' => $groupsMap])
        ]);
    }

    private function searchExtendSegmentTiles() {
        $activeMap = $this->searchEngineParseActiveInt();

        $segments = $this->db->query(
            '
                WITH counts AS (
                     SELECT cig.international_segment_id
                          , COUNT(DISTINCT cig.counteragent_id) AS counteragent_count
                     FROM pm.counteragent_international_segment AS cig
                     GROUP BY cig.international_segment_id
                     )
                   /*, totals AS (
                     SELECT ist.parent_international_segment_id AS international_segment_id
                          , SUM(COALESCE(c.counteragent_count, 0)) AS counteragent_count
                     FROM pm.international_segment_tree AS ist
                          LEFT JOIN counts AS c ON c.international_segment_id = ist.international_segment_id
                     GROUP BY ist.parent_international_segment_id
                     )*/
                SELECT isg.international_segment_id
                     , isg.parent_international_segment_id
                     , isg.segment_title
                     , t.counteragent_count
                FROM pm.international_segment AS isg
                   , counts AS t
                WHERE t.international_segment_id = isg.international_segment_id    
                ORDER BY isg.segment_title
            '
        );

        $segmentsMap = [];

        while ($row = $segments->fetch()) {
            $parentId = (int)$row['parent_international_segment_id'];

            $segmentsMap[$parentId] = $segmentsMap[$parentId] ?? [];

            $segmentsMap[$parentId][] = [
                'value' => (int)$row['international_segment_id'],
                'parent' => (int)$row['parent_international_segment_id'],
                'title' => $row['segment_title'],
                'descr' => $row['segment_title'],
                'selected' => isset($activeMap[$row['international_segment_id']]),
                'counts' => [
                    ['title' => 'Количество контрагентов: ', 'icon' => 'users', 'count' => $row['counteragent_count']]
                ]
            ];
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'html' => $this->view->getPartial('counteragents/search_engine/extend_tree_map', ['map' => $segmentsMap])
        ]);
    }

    private function searchExtendTechnologyTiles() {
        $activeMap    = $this->searchEngineParseActiveInt();
        $activeCatMap = $this->searchEngineParseActiveCatInt();

        $types = $this->db->query(
            '
                SELECT ptt.provide_technology_type_id
                     , ptt.type_title
                     , COUNT(DISTINCT cpt.counteragent_id) AS counteragent_count
                FROM pm.provide_technology_type AS ptt
                     LEFT JOIN pm.provide_technology AS pt 
                          ON   pt.provide_technology_type_id = ptt.provide_technology_type_id
                     LEFT JOIN pm.counteragent_provide_technology AS cpt 
                          ON   cpt.provide_technology_id = pt.provide_technology_id
                GROUP BY ptt.provide_technology_type_id
                       , ptt.type_title
                ORDER BY ptt.type_title
            '
        );

        $typesMap = [];
        $typesList = [];

        $typeActive = 1;

        while ($row = $types->fetch()) {
            $typesMap[$row['provide_technology_type_id']] = sizeof($typesList);

            $typesList[] = [
                'value' => (int)$row['provide_technology_type_id'],
                'title' => $row['type_title'],
                'descr' => $row['type_title'],
                'active' => $typeActive,
                'selectable' => 1,
                'selected' => isset($activeCatMap[$row['provide_technology_type_id']]),
                'counts' => [
                    //['title' => 'Количество технологий: ', 'icon' => 'users', 'count' => 0],
                    ['title' => 'Количество контрагентов: ', 'icon' => 'users', 'count' => $row['counteragent_count']],
                ]
            ];

            $typeActive = 0;
        }

        $technologies = $this->db->query(
            '
               SELECT pt.provide_technology_id
                    , pt.provide_technology_type_id
                    , pt.technology_title
                    , COUNT(DISTINCT cpt.counteragent_id) AS counteragent_count
               FROM pm.provide_technology AS pt
                    LEFT JOIN pm.counteragent_provide_technology AS cpt 
                         ON   cpt.provide_technology_id = pt.provide_technology_id
               GROUP BY pt.provide_technology_id
                      , pt.provide_technology_type_id
                      , pt.technology_title
               ORDER BY pt.technology_title
            '
        );

        $technologiesList = [];

        while ($row = $technologies->fetch()) {
            //$typesList[$typesMap[$row['provide_technology_type_id']]]['counts'][0]['count']++;

            $isActive = $typesList[$typesMap[$row['provide_technology_type_id']]]['active'];

            $technologiesList[] = [
                'value' => (int)$row['provide_technology_id'],
                'parent' => (int)$row['provide_technology_type_id'],
                'title' => $row['technology_title'],
                'descr' => $row['technology_title'],
                'selected' => isset($activeMap[$row['provide_technology_id']]),
                'class' => !$isActive ? 'hide' : '',
                'counts' => [
                    ['title' => 'Количество технологий: ', 'icon' => 'users', 'count' => $row['counteragent_count']]
                ]
            ];
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'html' => $this->view->getPartial('partials/eliza_search/panels/items/text_tile_list', ['list' => $technologiesList]),
            'category' => $this->view->getPartial('partials/eliza_search/panels/categories/text_list', ['list' => $typesList])
        ]);
    }

    private function searchExtendOpfTiles() {
        $activeMap = $this->searchEngineParseActiveInt();

        $opfs = $this->db->query(
            '
                SELECT ol.opf_id
                     , ol.opf_code
                     , ol.opf_type
                     , ol.opf_name_short
                     , ol.opf_name_full
                     , ol.counteragent_count
                FROM pm.opf_list AS ol
            '
        );

        $opfList = [];

        while($row = $opfs->fetch()) {
            $row['selected'] = isset($activeMap[(int)$row['opf_id']]);

            $opfList[] = $row;
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'html' => $this->view->getPartial('counteragents/search_engine/extend_opf_list', ['list' => $opfList])
        ]);
    }

    private function searchEngineManagerPrepareMenuTiles($managersList)
    {
        $managersTileList = [];

        if (sizeof($managersList)) {

            $managers = $this->db->query(
                '
                SELECT res.member_id AS id
                     , res.member_gender AS gender
                     , res.member_nick AS nick
                     , ma.avatar_ver AS avatar
                     , res.counteragent_count AS counteragent_count
                FROM (
                         SELECT m.member_id, m.member_nick, m.member_gender, COUNT(DISTINCT c.counteragent_id) AS counteragent_count
                         FROM pm.counteragent AS c
                            , public.members AS m
                         WHERE m.member_id IN('.implode(',', $managersList).')
                           AND m.member_id = c.manager_member_id
                         GROUP BY m.member_id
                                , m.member_nick
                                , m.member_gender
                     ) AS res
                     LEFT JOIN public.members_avatars AS ma ON ma.member_id = res.member_id
                ORDER BY res.member_nick
            '
            );

            while($row = $managers->fetch()) {
                if (!(int)$row['avatar']) {
                    $nick2 = mb_substr(preg_replace('/[^A-ZА-Я]/mu', '', $row['nick']), 0, 2);

                    if(mb_strlen($nick2) < 1) {
                        $nick2 = mb_strtoupper(mb_substr(preg_replace('/[^a-zа-я]/mu', '', $row['nick']), 0, 2));
                    }

                    $row['nick2'] = $nick2;
                }

                $managersTileList[] = [
                    'type' => 'user',
                    'value' => $row['id'],
                    'nick' => $row['nick'],
                    'nick2' => $row['nick2'],
                    'gender' => $row['gender'],
                    'version' => $row['avatar'],
                    'selected' => 0,
                    'descr' => 'Исключить менеджера из фильтра',
                    'counts' => [
                        ['title' => 'Количество контрагентов: ', 'count' => $row['counteragent_count']]
                    ]
                ];
            }
        }

        return $managersTileList;
    }

    private function searchEngineGroupPrepareMenuTiles($groupsList)
    {
        $groupsTileList = [];

        if (sizeof($groupsList)) {
            $groups = $this->db->query(
                '
                    WITH res AS (
                             SELECT gr.parent_id
                                  , gl.group_title
                                  , count(*) AS counteragent_count
                             FROM pm.counteragent AS c
                                , public.members AS m
                                , public.groups_relationships AS gr
                                , public.groups_langs AS gl
                             WHERE m.member_id = c.manager_member_id
                               AND gr.group_id = m.member_group
                               AND gl.group_id = gr.parent_id
                               AND gl.lang_id = 1
                             GROUP BY gr.parent_id
                                    , gl.group_title
                         )
                    SELECT g.group_id AS node_id
                         , COALESCE(g.group_parent_id, 0) AS parent_id
                         , res.group_title AS title
                         , res.counteragent_count
                    FROM res
                       , public.groups AS g
                    WHERE g.group_id = res.parent_id
                      AND g.group_id IN ('.implode(',', $groupsList).')
                    ORDER BY res.group_title            
                '
            );

            while($row = $groups->fetch()) {
                $groupsTileList[] = [
                    'type'  => 'text',
                    'value' => (int)$row['node_id'],
                    'parent' => (int)$row['parent_id'],
                    'title' => $row['title'],
                    'descr' => $row['title'],
                    'selected' => 0,
                    'counts' => [
                        ['title' => 'Количество контрагентов: ', 'icon' => 'users', 'count' => $row['counteragent_count']]
                    ]
                ];
            }
        }

        return $groupsTileList;
    }

    private function searchEngineSegmentPrepareMenuTiles($segmentsList)
    {
        $segmentsTileList = [];

        if (sizeof($segmentsList)) {
            $segments = $this->db->query(
                '
                WITH counts AS (
                     SELECT cig.international_segment_id
                          , COUNT(DISTINCT cig.counteragent_id) AS counteragent_count
                     FROM pm.counteragent_international_segment AS cig
                     GROUP BY cig.international_segment_id
                     )
                   , totals AS (
                     SELECT ist.parent_international_segment_id AS international_segment_id
                          , SUM(COALESCE(c.counteragent_count, 0)) AS counteragent_count
                     FROM pm.international_segment_tree AS ist
                          LEFT JOIN counts AS c ON c.international_segment_id = ist.international_segment_id
                     GROUP BY ist.parent_international_segment_id
                     )
                SELECT isg.international_segment_id
                     , isg.parent_international_segment_id
                     , isg.segment_title
                     , t.counteragent_count
                FROM pm.international_segment AS isg
                   , totals AS t
                WHERE t.international_segment_id = isg.international_segment_id  
                  AND isg.international_segment_id IN ('.implode(',', $segmentsList).')
                ORDER BY isg.segment_title
            '
            );

            while($row = $segments->fetch()) {
                $segmentsTileList[] = [
                    'type'  => 'text',
                    'value' => (int)$row['international_segment_id'],
                    'parent' => (int)$row['parent_international_segment_id'],
                    'title' => $row['segment_title'],
                    'descr' => $row['segment_title'],
                    'selected' => 0,
                    'counts' => [
                        ['title' => 'Количество контрагентов: ', 'icon' => 'users', 'count' => $row['counteragent_count']]
                    ]
                ];
            }
        }

        return $segmentsTileList;
    }

    private function searchEngineTechnologyPrepareMenuTiles($technologiesList, $technologiesCatList = [])
    {
        $technologiesTileList = [];

        if (sizeof($technologiesCatList)) {
            $types = $this->db->query(
                '
                    SELECT ptt.provide_technology_type_id
                         , ptt.type_title
                    FROM pm.provide_technology_type AS ptt
                    WHERE ptt.provide_technology_type_id IN ('.implode(',', $technologiesCatList).')
                    ORDER BY ptt.type_title
                '
            );

            while($row = $types->fetch()) {
                $technologiesTileList[] = [
                    'type'  => 'text',
                    'value' => (int)$row['provide_technology_type_id'],
                    'category' => 1,
                    'title' => '<b class="elz bold fn fn-danger">'.$row['type_title'].'</b>',
                    'descr' => $row['type_title'],
                    'selected' => 0
                ];
            }
        }

        if (sizeof($technologiesList)) {
            $technologies = $this->db->query(
                '
                   SELECT pt.provide_technology_id
                        , pt.provide_technology_type_id
                        , pt.technology_title
                        , COUNT(DISTINCT cpt.provide_technology_id) AS counteragent_count
                   FROM pm.provide_technology AS pt
                        LEFT JOIN pm.counteragent_provide_technology AS cpt 
                             ON   cpt.provide_technology_id = pt.provide_technology_id
                   WHERE pt.provide_technology_id IN ('.implode(',', $technologiesList).')
                   GROUP BY pt.provide_technology_id
                          , pt.provide_technology_type_id
                          , pt.technology_title
                   ORDER BY pt.technology_title
                '
            );

            while($row = $technologies->fetch()) {
                $technologiesTileList[] = [
                    'type'  => 'text',
                    'value' => (int)$row['provide_technology_id'],
                    'parent' => (int)$row['provide_technology_type_id'],
                    'title' => $row['technology_title'],
                    'descr' => $row['technology_title'],
                    'selected' => 0,
                    'counts' => [
                        ['title' => 'Количество контрагентов: ', 'icon' => 'users', 'count' => $row['counteragent_count']]
                    ]
                ];
            }
        }

        return $technologiesTileList;
    }

    private function searchEngineOpfPrepareMenuTiles($opfList)
    {
        $opfTileList = [];

        if (sizeof($opfList)) {
            $opfs = $this->db->query(
                '
                    SELECT ol.opf_id
                         , ol.opf_code
                         , ol.opf_type
                         , ol.opf_name_short
                         , ol.opf_name_full
                         , ol.counteragent_count
                    FROM pm.opf_list AS ol
                    WHERE ol.opf_id IN (' . implode(',', $opfList) . ')
                '
            );

            while ($row = $opfs->fetch()) {
                $opfTileList[] = [
                    'type' => 'text',
                    'value' => $row['opf_id'],
                    'title' => $row['opf_name_short'],
                    'selected' => 0,
                    'descr' => 'Исключить ОПФ из фильтра',
                    'counts' => [
                        ['title' => 'Количество контрагентов: ', 'count' => $row['counteragent_count']]
                    ]
                ];
            }
        }

        return $opfTileList;
    }

    private function searchEngineParseActiveInt() {
        $activeList = explode(',', $this->request->getPost('active'));

        $activeMap = [];

        for($a = 0, $len = sizeof($activeList); $a < $len; $a++) {
            $intId = (int)$activeList[$a];

            if (!$intId) {
                continue;
            }

            $activeMap[$intId] = $intId;
        }

        return $activeMap;
    }

    private function searchEngineParseActiveCatInt() {
        $activeList = explode(',', $this->request->getPost('active_cat'));

        $activeMap = [];

        for($a = 0, $len = sizeof($activeList); $a < $len; $a++) {
            $intId = (int)$activeList[$a];

            if (!$intId) {
                continue;
            }

            $activeMap[$intId] = $intId;
        }

        return $activeMap;
    }

    private function linkAdd() {
        $title = trim($this->request->getPost('title'));
        $uri = trim($this->request->getPost('uri'));
        $public = (int)$this->request->getPost('public');
        $memberId = $this->user->getId();

        if ($title == '') {
            $this->sendResponseAjax([
                'state' => 'no'
            ]);
        }

        $this->db->query(
            '
                SELECT pm.link_create(:mid, :tid, :pub, :tit, :uri)
            ',
            [
                'mid' => $memberId,
                'tid' => 1,
                'pub' => $public,
                'tit' => $title,
                'uri' => $uri,
            ]
        );

        $this->sendResponseAjax([
            'state' => 'yes'
        ]);
    }

    protected function apiCounteragent_get() {
        $this->sendResponseAjax([
            'state' => 'yes',
            'agent' => $this->db->query(
                '
                    SELECT c.counteragent_title
                         , cr.requisite_inn
                    FROM pm.counteragent AS c
                       , pm.counteragent_requisite AS cr
                    WHERE c.counteragent_id = :cid
                      AND cr.counteragent_id = c.counteragent_id
                      AND cr.requisite_active = 1
                ',
                [
                    'cid' => (int)$this->request->getPost('counteragent_id')
                ]
            )->fetch()
        ]);
    }

    protected function apiLink_get() {
        $mode = trim($this->request->getPost('mode')) == 'private' ? 'private' : 'public';

        $stmtWhere = '(l.link_public = 1 OR (l.link_public = 0 AND l.member_id = :mid))';

        if ($mode == 'private') {
            $stmtWhere = 'l.member_id = :mid';
        }

        $html = '';

        $links = $this->db->query(
            '
                SELECT l.link_id
                     , l.link_title
                     , l.link_uri
                     , l.link_public
                     , m.member_id
                     , m.member_nick
                FROM pm.link AS l
                   , public.members AS m
                WHERE l.link_type_id = 1
                  AND m.member_id = l.member_id
                  AND '.$stmtWhere.'
                ORDER BY l.link_title
            ',
            [
                'mid' => $this->user->getId()
            ]
        );

        while($row = $links->fetch()) {
            $html .= $this->view->getPartial('counteragents/partials/link_item', [
                'link' => $row,
                'mode' => $mode
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'html' => $html
        ]);
    }

    protected function apiLink_delete() {
        $linkId = (int)$this->request->getPost('link_id');

        $this->db->query(
            ' SELECT pm.link_delete(:mid, :lid)',
            [
                'mid' => $this->user->getId(),
                'lid' => $linkId,
            ]
        );

        $this->sendResponseAjax([
            'state' => 'yes'
        ]);
    }

    protected function apiLink_public() {
        $linkId = (int)$this->request->getPost('link_id');
        $public = (int)$this->request->getPost('public') ? 1 : 0;

        $this->db->query(
            ' SELECT pm.link_update_public(:mid, :lid, :pub)',
            [
                'mid' => $this->user->getId(),
                'lid' => $linkId,
                'pub' => $public
            ]
        );

        $this->sendResponseAjax([
            'state' => 'yes'
        ]);
    }

    protected function apiInnNameSearch() {
        $inn = '%'.trim(mb_strtoupper($this->request->getPost('inn'))).'%';

        $this->sendResponseAjax([
            'state' => 'yes',
            'counteragents' => $inn != '%%' ? $this->db->query(
                '
                    SELECT cr.requisite_inn
                         , COALESCE(cr.requisite_kpp, \'нет\') AS requisite_kpp
                         , c.counteragent_id
                         , c.counteragent_title
                    FROM pm.counteragent_requisite AS cr
                       , pm.counteragent AS c
                    WHERE cr.requisite_active = 1
                      AND (cr.requisite_inn LIKE :inn OR UPPER(c.counteragent_title) LIKE :inn)
                      AND c.counteragent_id = cr.counteragent_id
                    ORDER BY c.counteragent_title
                    LIMIT 10
                ',
                [
                    'inn' => $inn
                ]
            )->fetchAll() : []
        ]);
    }

    protected function apiSignersList()
    {
        $this->sendResponseAjax([
            'state' => 'yes',
            'signers' => $this->db->query(
                '
                SELECT ct.contact_id
                     , ct.contact_nickname
                FROM pm.counteragent AS ca
                   , pm.contact_node_contact AS cnc
                   , pm.contact AS ct
                   , pm.contact_trait_contact AS ctc
                WHERE ca.counteragent_id = :cid
                  AND cnc.contact_node_id = ca.contact_node_id
                  AND ct.contact_id = cnc.contact_id
                  AND ctc.contact_id = ct.contact_id
                  AND ctc.contact_trait_id = 6 -- имеет право подписи
            ',
                [
                    'cid' => (int)$this->request->getPost('counteragent_id')
                ]
            )->fetchAll()
        ]);
    }

    protected function apiContact_list()
    {
        $counterAgentId = (int)$this->request->getPost('counteragent_id');
        $contactRoleList = json_decode($this->request->getPost('role_list') ?? '[]', true);

        $roleIds = [];

        foreach ($contactRoleList as $roleId) {
            $roleIds[] = $roleId;
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'signers' => $this->db->query(
                '
                    SELECT c.contact_id
                         , c.contact_nickname
                         , cr.contact_role_id
                         , cr.role_title
                    FROM pm.counteragent AS ct
                       , pm.contact_node_contact AS cnc
                       , pm.contact AS c
                       , pm.contact_role AS cr
                    WHERE ct.counteragent_id = :cid
                      AND cnc.contact_node_id = ct.contact_node_id
                      AND c.contact_id = cnc.contact_id
                      AND c.contact_hide = 0
                      AND cr.contact_role_id = c.contact_role_id
                      '.(sizeof($roleIds) ? ' AND c.contact_role_id IN ('.implode(',', $roleIds).') ' : '').'
                    ORDER BY cr.role_title
                           , c.contact_nickname
                ',
                [
                    'cid' => $counterAgentId
                ]
            )->fetchAll()
        ]);
    }
}