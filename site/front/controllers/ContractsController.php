<?php
namespace Site\Front\Controllers;


use Core\Extender\ControllerApp;
use Site\Front\Handlers\ContractsHandler;
use Site\Front\Handlers\FilesHandler;
use Site\Front\Handlers\SearchHandler;

/**
 * Class ContractsController
 * @package Site\Front\Controllers
 */
class ContractsController extends ControllerApp
{
    const ITEMS_PER_PAGE = 50;
    const PAGE_LEFT_RIGHT_DELTA = 3;

    public function initialize()
    {
        parent::initialize();

        $this->hapi->setHapiController('contracts');
    }

    public function indexAction()
    {
        if ($this->request->isPost() && $this->request->isAjax() && (int)$this->request->get('filter_add') == 1) {
            $this->linkAdd();
        }

        $this->view->setVar('mainMenuData', [
            'active' => 'pm_menu_contracts',
            'overlay' => 1,
            'template' => 'contracts/menu_index',
            'template_panels' => 'contracts/panels_index',
            'icon' => 'rules.svg',
            'title' => 'Договора',
        ]);

        $this->hapi->setHapiAction('index');

        $result = $this->searchEngine();
        $filtersMap = $result['filters'];

        $sortingList = $result['sorting_list'];
        $columnsList = $result['columns_list'];

        $prolongations = $this->db->query(
            '
                WITH counts AS (
                     SELECT COUNT(*) AS prolongation_count
                          , c.contract_prolongation_id
                     FROM pm.contract AS c 
                     GROUP BY c.contract_prolongation_id
                )
                SELECT cr.contract_prolongation_id AS prolongation_id
                     , cr.prolongation_title
                     , COALESCE(cs.prolongation_count, 0) AS prolongation_count
                FROM pm.contract_prolongation AS cr
                     LEFT JOIN counts AS cs  ON cs.contract_prolongation_id = cr.contract_prolongation_id
                ORDER BY cr.prolongation_order 
            '
        );

        $prolongationsFilterList = [];

        while($row = $prolongations->fetch()) {
            $prolongationsFilterList[] = [
                'name' => 'prolongation_'.$row['prolongation_id'],
                'count' => $row['prolongation_count'],
                'title' => $row['prolongation_title'],
                'checked' => ($filtersMap['prolongation'] ?? 0) == $row['prolongation_id'],
            ];
        }

        $statuses = $this->db->query(
            '
                WITH counts AS (
                     SELECT COUNT(*) AS status_count
                          , c.contract_status_id
                     FROM pm.contract AS c 
                     GROUP BY c.contract_status_id
                )
                SELECT cs.contract_status_id AS status_id
                     , cs.status_title
                     , cs.status_name
                     , COALESCE(ct.status_count, 0) AS status_count
                FROM pm.contract_status AS cs
                     LEFT JOIN counts AS ct  ON ct.contract_status_id = cs.contract_status_id
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

        $responsibleList = $filtersMap['responsible'] ?? [];
        $responsibleTileList = $this->searchEngineManagerPrepareMenuTiles($responsibleList);

        $groupList = $filtersMap['group'] ?? [];
        $groupTileList = $this->searchEngineGroupPrepareMenuTiles($groupList);

        $customerList = $filtersMap['customer'] ?? [];
        $customerTileList = $this->searchEngineCounterAgentsPrepareMenuTiles(2, $customerList);

        $executorList = $filtersMap['executor'] ?? [];
        $executorTileList = $this->searchEngineCounterAgentsPrepareMenuTiles(1, $executorList);

        $valNumber = $filtersMap['number'] ?? '';
        $valTitle = $filtersMap['title'] ?? '';

        $elizaSearch = [
            'filter_inputs' => [
                ['filter' => 'input', 'value' => $valNumber, 'name' => 'number', 'title' => 'Номер',    'placeholder' => 'Номер договора',    'position' => 'top'],
                ['filter' => 'input', 'value' => $valTitle,  'name' => 'title',  'title' => 'Название', 'placeholder' => 'Название договора', 'position' => 'bottom']
            ],
            'filter_items' => [
                ['filter' => 'switch',     'name' => 'only_mine',    'title' => 'Мои договора', 'checked' => $filtersMap['only_mine'] ?? 0],
                ['filter' => 'check',      'name' => 'status',       'title' => 'Статус',        'list' => $statusesFilterList],
                ['filter' => 'radio_flex', 'name' => 'prolongation', 'title' => 'Пролонгация',   'list' => $prolongationsFilterList],
                ['filter' => 'extend',     'name' => 'customer',     'title' => 'Заказчики',     'category' => 0, 'extended' => ['active' => 1, 'title' => 'Выбор заказчиков'],    'reset' => ['active' => sizeof($customerList),    'title' => 'Cброс', 'descr' => 'Снять всех выбранных заказчиков'],    'icon' => 'users',      'list' => $customerTileList],
                ['filter' => 'extend',     'name' => 'executor',     'title' => 'Исполнители',   'category' => 0, 'extended' => ['active' => 1, 'title' => 'Выбор исполнителей'],  'reset' => ['active' => sizeof($executorList),    'title' => 'Cброс', 'descr' => 'Снять всех выбранных исполнителей'],  'icon' => 'users',      'list' => $executorTileList],
                ['filter' => 'extend',     'name' => 'responsible',  'title' => 'Отвественные',  'category' => 0, 'extended' => ['active' => 1, 'title' => 'Выбор ответственных'], 'reset' => ['active' => sizeof($responsibleList), 'title' => 'Cброс', 'descr' => 'Снять всех выбранных ответственных'], 'icon' => 'user',       'list' => $responsibleTileList],
                ['filter' => 'extend',     'name' => 'group',        'title' => 'Подразделения', 'category' => 0, 'extended' => ['active' => 1, 'title' => 'Выбор подразделений'], 'reset' => ['active' => sizeof($groupList),       'title' => 'Cброс', 'descr' => 'Снять все выбранные подразделения'],  'icon' => 'users2 s20', 'list' => $groupTileList],

            ],
            'control_sections' => [
                ['control' => 'orders',  'name' => 'sorting', 'title' => 'Сортировка', 'list' => $sortingList],
                ['control' => 'columns', 'name' => 'columns', 'title' => 'Столбцы',    'list' => $columnsList],
            ]
        ];

        $this->view->setVar('searchEngineData', $elizaSearch);

        $this->view->setVar('searchEnginePanelsList', [
            ['type' => 'panel_search', 'data' => ['name' => 'customer',    'icon' => 'users',      'layout' => 'columns_1', 'width' => 'w960', 'height' => 'h640', 'placeholder' => 'Поиск заказчика']],
            ['type' => 'panel_search', 'data' => ['name' => 'executor',    'icon' => 'users',      'layout' => 'columns_1', 'width' => 'w960', 'height' => 'h640', 'placeholder' => 'Поиск исполнителя']],
            ['type' => 'panel_search', 'data' => ['name' => 'responsible', 'icon' => 'user',       'layout' => 'columns_1', 'width' => 'w960', 'height' => 'h640', 'placeholder' => 'Поиск ответственного']],
            ['type' => 'panel_title',  'data' => ['name' => 'group',       'icon' => 'users2 s20', 'layout' => 'columns_1', 'width' => 'w960', 'height' => 'h640', 'title' => 'Выбор подразделения']]
        ]);
    }

    public function contractAction($contractId = 0, $section = '')
    {
        $contractRow = $this->db->query(
            '
                SELECT c.contract_id
                     , c.contract_title
                     , c.contract_comment
                     , c.contract_number
                     , c.file_node_id
                     , CAST(c.contract_create_date AS timestamp(0)) AS contract_create_date
                     , CAST(c.contract_update_date AS timestamp(0)) AS contract_update_date
                     , CAST(c.contract_finish_date AS date) AS contract_finish_date
                     , CAST(c.contract_sign_date   AS date) AS contract_sign_date
                     , cs.contract_status_id
                     , cs.status_title
                     , cp.contract_prolongation_id
                     , cp.prolongation_title
                     , cpd.contract_prolongation_duration_id
                     , cpd.duration_month
                     , pt.payment_term_id
                     , pt.term_title
                     , cr.currency_id
                     , cr.currency_title
                     , c.create_member_id
                     , c.payment_before
                     , m1.member_nick AS create_member_nick
                     , c.update_member_id
                     , m2.member_nick AS update_member_nick
                     , c.responsible_member_id
                     , m3.member_nick AS responsible_member_nick
                FROM pm.contract AS c
                   , pm.contract_status AS cs
                   , pm.contract_prolongation AS cp
                   , pm.contract_prolongation_duration AS cpd
                   , pm.payment_term AS pt
                   , common.currency AS cr
                   , public.members AS m1
                   , public.members AS m2
                   , public.members AS m3
                WHERE c.contract_id = :cid
                  AND cs.contract_status_id = c.contract_status_id
                  AND cp.contract_prolongation_id = c.contract_prolongation_id
                  AND cpd.contract_prolongation_duration_id = c.contract_prolongation_duration_id
                  AND pt.payment_term_id = c.payment_term_id                
                  AND cr.currency_id = c.currency_id                
                  AND m1.member_id = c.create_member_id
                  AND m2.member_id = c.update_member_id
                  AND m3.member_id = c.responsible_member_id
            ',
            [
                'cid' => $contractId
            ]
        )->fetch();

        $this->view->setLayout('contract');

        $this->view->setVar('contractRow', $contractRow);

        $this->view->setVar('mainMenuData', [
            'active' => 'pm_menu_contracts',
            'overlay' => 1,
            'template' => 'contracts/menu_contract',
            'icon' => 'rules.svg',
            'title' => 'Договор',
        ]);

        $this->hapi->setHapiAction('contract');

        $sectionsList = [
            ['uri' => '',          'icon' => 'notification', 'selected' => '', 'title' => 'Общее'],
            ['uri' => 'orders',    'icon' => 'basket',       'selected' => '', 'title' => 'Заказы'],
            ['uri' => 'services',  'icon' => 'archive',      'selected' => '', 'title' => 'Услуги'],
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

        $this->view->setVar('contractId', $contractId);
        $this->view->setVar('contractSectionsList', $sectionsList);

        if ($section == '') {
            $this->contractSectionContract($contractId, $contractRow);
        }
        else if ($section == 'orders') {
            $this->contractSectionOrders($contractRow);
        }
        else if ($section == 'documents') {
            $this->contractSectionDocuments($contractRow);
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

    private function contractSectionContract($contractId, $contractRow)
    {

        if ($this->request->isPost()) {
            $act = $this->request->get('act');

            if ($act == 'contract_edit_title') {
                $this->contractEditTitle($contractId);
            }
            else if ($act == 'contract_edit_number') {
                $this->contractEditNumber($contractId);
            }
            else if ($act == 'contract_edit_finish') {
                $this->contractEditFinishDate($contractId);
            }
            else if ($act == 'contract_edit_sign') {
                $this->contractEditSignDate($contractId);
            }
            else if ($act == 'contract_edit_responsible') {
                $this->contractEditResponsible($contractId);
            }
            else if ($act == 'contract_edit_payment') {
                $this->contractEditPaymentTerm($contractId);
            }
            else if ($act == 'contract_edit_currency') {
                $this->contractEditPaymentCurrency($contractId);
            }
            else if ($act == 'contract_edit_prolongation') {
                $this->contractEditProlongation($contractId);
            }
            else if ($act == 'contract_edit_comment') {
                $this->contractEditComment($contractId);
            }
            else if ($act == 'file_upload') {
                $this->contractUploadFile($contractRow['file_node_id']);
            }
        }
        
        
        $this->hapi->setHapiAction('contract');

        $roles = $this->db->query(
            '
                SELECT ccr.contract_counteragent_role_id
                     , ccr.role_title
                     , c.counteragent_id
                     , c.counteragent_title
                     , COALESCE(ct.contact_id, 0) AS contact_id
                     , COALESCE(ct.contact_nickname, \'\') AS contact_nickname
                     , COALESCE(ctr.role_title, \'\') AS contact_role_title
                     , c.contact_node_id
                FROM pm.contract_counteragent AS cc
                     CROSS JOIN pm.contract_counteragent_role AS ccr
                     LEFT JOIN pm.contract_counteragent_contact AS ccc
                          ON   ccc.contract_id = cc.contract_id
                           AND ccc.counteragent_id = cc.counteragent_id
                     LEFT JOIN pm.contact AS ct
                          ON   ct.contact_id = ccc.contact_id
                     LEFT JOIN pm.contact_role AS ctr 
                          ON   ctr.contact_role_id = ct.contact_role_id
                   , pm.counteragent AS c
                WHERE cc.contract_id = :cid
                  AND ccr.contract_counteragent_role_id = cc.contract_counteragent_role_id
                  AND c.counteragent_id = cc.counteragent_id
                ORDER BY ccr.role_title    
            ',
            [
                'cid' => $contractId
            ]
        );

        $rolesList = [];
        while($row = $roles->fetch()) {
            $signerOptions = [
                [0, 'Выберите подписанта', true]
            ];

            $signers = $this->db->query(
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
                    ORDER BY ct.contact_nickname  
                ',
                [
                    'cid' => $row['counteragent_id']
                ]
            );

            while($signer = $signers->fetch()) {
                $signerOptions[] = [$signer['contact_id'], $signer['contact_nickname']];
            }

            $row['signer_options'] = $signerOptions;

            $rolesList[] = $row;
        }

        $this->view->setVar('rolesList', $rolesList);

        $this->view->pick('contracts/contract');

        $terms = $this->db->query(
            '
                    SELECT pt.payment_term_id
                         , pt.term_title
                    FROM pm.payment_term AS pt
                    ORDER BY pt.term_title
                '
        );

        $termOptions = [];

        while($row = $terms->fetch()) {
            $termOptions[] = [$row['payment_term_id'], $row['term_title']];
        }

        $this->view->setVar('termOptions', $termOptions);

        $currencies = $this->db->query(
            '
                    SELECT c.currency_id
                         , c.currency_title
                    FROM common.currency AS c
                    ORDER BY c.currency_title
                '
        );

        $currencyOptions = [];

        while($row = $currencies->fetch()) {
            $currencyOptions[] = [$row['currency_id'], $row['currency_title']];
        }

        $this->view->setVar('currencyOptions', $currencyOptions);

        $payDayOptions = [];

        for($a = 1; $a <= 31; $a++) {
            $payDayOptions[] = [$a, $a.'го числа'];
        }

        $this->view->setVar('payDayOptions', $payDayOptions);


        $prolongations= $this->db->query(
            '
                SELECT cp.contract_prolongation_id
                     , cp.prolongation_title
                FROM pm.contract_prolongation AS cp
                ORDER BY cp.prolongation_title
            '
        );

        $prolongationOptions = [];

        while($row = $prolongations->fetch()) {
            $prolongationOptions[] = [$row['contract_prolongation_id'], $row['prolongation_title']];
        }

        $this->view->setVar('prolongationOptions', $prolongationOptions);

        $durations = $this->db->query(
            '
                SELECT cpd.contract_prolongation_duration_id
                     , cpd.duration_month
                FROM pm.contract_prolongation_duration AS cpd
                WHERE cpd.duration_month <> 0  
                ORDER BY cpd.duration_month
            '
        );

        $durationOptions = [[1, 'Выберите продолжительность', true]];

        while($row = $durations->fetch()) {
            $durationOptions[] = [$row['contract_prolongation_duration_id'], $row['duration_month'].' мес.'];
        }

        $this->view->setVar('durationOptions', $durationOptions);


        $FileHandler = new FilesHandler($contractRow['file_node_id']);

        $filesList = $FileHandler->getFilesByTraits([3, 4]);

        $filesMap = [
            3 => [
                'file_id' => 0,
                'file_path' => '',
                'title' => 'Черновик договора',
                'date' => '',
                'trait' => 3,
                'icon' => 'file-text2'
            ],
            4 => [
                'file_id' => 0,
                'file_path' => '',
                'title' => 'Скан договора',
                'date' => '',
                'trait' => 4,
                'icon' => 'rules'
            ]
        ];

        foreach ($filesList as $file) {
            $filesMap[$file['file_type_id']]['file_id'] = (int)$file['file_id'];
            $filesMap[$file['file_type_id']]['date'] = $file['file_create_date'];
            $filesMap[$file['file_type_id']]['file_path'] = $file['file_path'];
        }


        $this->view->setVar('contractFilesMap', $filesMap);
    }

    private function contractSectionOrders($contractRow)
    {
        $this->view->pick('contracts/orders');
        $this->view->setVar('mainSectionHeaderRightTemplate', 'contracts/header_right_orders');
        $this->view->setVar('ordersList', []);
    }

    private function contractSectionDocuments($contractRow)
    {
        $files = $this->db->query(
            '
                WITH nodes AS (
                        SELECT '.$contractRow['file_node_id'].' AS file_node_id
                             , \'contract\' AS entity_type
                             , '.$contractRow['contract_id'].' AS entity_id
                             , :name AS entity_title
                             , 1 AS entity_order
                             , \'Договор\' AS entity_type_title
                        UNION
                        SELECT c.file_node_id
                             , \'contact\' AS entity_type
                             , c.contact_id AS entity_id
                             , c.contact_nickname AS entity_title     
                             , 2 AS entity_order 
                             , \'Контакты\' AS entity_type_title
                        FROM pm.contract_counteragent_contact AS ccc
                           , pm.contact AS c
                        WHERE ccc.contract_id = :cid
                          AND c.contact_id = ccc.contact_id 
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
                'name' => $contractRow['contract_number'],
                'cid' => $contractRow['contract_id']
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

    private function contractEditTitle($contractId)
    {
        $title = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.contract_update_title(:cid, :mid, :tit) AS res',
            [
                'cid' => $contractId,
                'mid' => $this->user->getId(),
                'tit' => $title,
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
            'result_content' => htmlspecialchars($title),
            'notification' => 'Данные сохранены'
        ]);
    }

    private function contractEditNumber($contractId)
    {
        $number = trim($this->request->getPost('number'));

        $result = (int)$this->db->query(
            'SELECT pm.contract_update_number(:cid, :mid, :tit) AS res',
            [
                'cid' => $contractId,
                'mid' => $this->user->getId(),
                'tit' => $number,
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
            'result_content' => htmlspecialchars($number),
            'notification' => 'Данные сохранены'
        ]);
    }

    private function contractEditFinishDate($contractId)
    {
        $finish = trim($this->request->getPost('finish'));

        $result = (int)$this->db->query(
            'SELECT pm.contract_update_finish_date(:cid, :mid, :tit) AS res',
            [
                'cid' => $contractId,
                'mid' => $this->user->getId(),
                'tit' => $finish.' 23:59:59',
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
            'result_content' => htmlspecialchars($finish),
            'notification' => 'Данные сохранены'
        ]);
    }

    private function contractEditSignDate($contractId)
    {
        $sign = trim($this->request->getPost('sign'));

        $result = (int)$this->db->query(
            'SELECT pm.contract_update_sign_date(:cid, :mid, :tit) AS res',
            [
                'cid' => $contractId,
                'mid' => $this->user->getId(),
                'tit' => $sign.' 23:59:59',
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
            'result_content' => htmlspecialchars($sign),
            'notification' => 'Данные сохранены'
        ]);
    }

    private function contractEditResponsible($contractId)
    {
        $responsibleId = (int)$this->request->getPost('responsible_id');

        $result = (int)$this->db->query(
            'SELECT pm.contract_update_responsible(:cid, :mid, :rid) AS res',
            [
                'cid' => $contractId,
                'mid' => $this->user->getId(),
                'rid' => $responsibleId,
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
            'result_content' => htmlspecialchars(trim($this->request->getPost('responsible'))),
            'notification' => 'Данные сохранены'
        ]);
    }

    private function contractEditPaymentTerm($contractId)
    {
        $paymentTermId = (int)$this->request->getPost('term');
        $paymentBefore= (int)$this->request->getPost('before');

        $result = (int)$this->db->query(
            'SELECT pm.contract_update_payment_term(:cid, :mid, :tid, :bef) AS res',
            [
                'cid' => $contractId,
                'mid' => $this->user->getId(),
                'tid' => $paymentTermId,
                'bef' => $paymentBefore
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
            'result_content1' => $this->db->query(
                'SELECT term_title FROM pm.payment_term WHERE payment_term_id = :tid',
                ['tid' => $paymentTermId]
            )->fetch()['term_title'],
            'result_content2' => $paymentBefore.'го числа',
            'notification' => 'Данные сохранены'
        ]);
    }

    private function contractEditPaymentCurrency($contractId)
    {
        $currencyId = (int)$this->request->getPost('currency');

        $result = (int)$this->db->query(
            'SELECT pm.contract_update_currency(:cid, :mid, :rid) AS res',
            [
                'cid' => $contractId,
                'mid' => $this->user->getId(),
                'rid' => $currencyId
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
            'result_content' => $this->db->query(
                'SELECT currency_title FROM common.currency WHERE currency_id = :rid',
                ['rid' => $currencyId]
            )->fetch()['currency_title'],
            'notification' => 'Данные сохранены'
        ]);
    }

    private function contractEditProlongation($contractId)
    {
        $prolongationId = (int)$this->request->getPost('prolongation');
        $durationId= (int)$this->request->getPost('duration');

        $result = (int)$this->db->query(
            'SELECT pm.contract_update_prolongation(:cid, :mid, :pid, :did) AS res',
            [
                'cid' => $contractId,
                'mid' => $this->user->getId(),
                'pid' => $prolongationId,
                'did' => $durationId
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
            'result_content1' => $this->db->query(
                'SELECT prolongation_title FROM pm.contract_prolongation WHERE contract_prolongation_id = :pid',
                ['pid' => $prolongationId]
            )->fetch()['prolongation_title'],
            'result_content2' => $prolongationId == 2 ? '' : $this->db->query(
                'SELECT duration_month FROM pm.contract_prolongation_duration WHERE contract_prolongation_duration_id = :did',
                ['did' => $durationId]
            )->fetch()['duration_month'].' мес.',
            'notification' => 'Данные сохранены'
        ]);
    }

    private function contractEditComment($contractId)
    {
        $comment = trim($this->request->getPost('comment'));

        $result = (int)$this->db->query(
            'SELECT pm.contract_update_comment(:cid, :mid, :cmt) AS res',
            [
                'cid' => $contractId,
                'mid' => $this->user->getId(),
                'cmt' => $comment
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
            'result_content' => nl2br($comment),
            'notification' => 'Данные сохранены'
        ]);
    }

    private function contractUploadFile($fileNodeId) {
        $contractFile = $_FILES['file'];
        $fileTypeId = (int)$this->request->getPost('type');
        $fileTraitId = (int)$this->request->getPost('trait');

        if (!$contractFile['error']) {
            $fileHandler = new FilesHandler($fileNodeId);

            $fileId = $fileHandler->addFile($fileTypeId, $contractFile);

            $fileHandler->linkSingleTrait($fileId, $fileTraitId);

            $fileData = $fileHandler->getFileById($fileId);

            $this->sendResponseAjax([
                'state' => 'yes',
                'file' => $fileData['file_path'],
                'date' => $fileData['file_create_date']
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'no'
        ]);
    }

    private function searchEngine()
    {
        $SearchHandler = new SearchHandler();
        $SearchHandler->setEntityHandler(new ContractsHandler());

        return $SearchHandler->performSearch('contracts');
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
                     , res.contract_count AS contract_count
                FROM (
                         SELECT m.member_id, m.member_nick, m.member_gender, COUNT(DISTINCT c.contract_id) AS contract_count
                         FROM pm.contract AS c
                            , public.members AS m
                         WHERE m.member_id IN('.implode(',', $managersList).')
                           AND m.member_id = c.responsible_member_id
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
                    'descr' => 'Исключить ответственного из фильтра',
                    'counts' => [
                        ['title' => 'Количество договоров: ', 'count' => $row['contract_count']]
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
                                  , count(*) AS contract_count
                             FROM pm.contract AS c
                                , public.members AS m
                                , public.groups_relationships AS gr
                                , public.groups_langs AS gl
                             WHERE m.member_id = c.responsible_member_id
                               AND gr.group_id = m.member_group
                               AND gl.group_id = gr.parent_id
                               AND gl.lang_id = 1
                             GROUP BY gr.parent_id
                                    , gl.group_title
                         )
                    SELECT g.group_id AS node_id
                         , COALESCE(g.group_parent_id, 0) AS parent_id
                         , res.group_title AS title
                         , res.contract_count
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
                        ['title' => 'Количество контрактов: ', 'icon' => 'rules', 'count' => $row['contract_count']]
                    ]
                ];
            }
        }

        return $groupsTileList;
    }

    private function searchEngineCounterAgentsPrepareMenuTiles($roleId, $counteragentsList)
    {
        $counteragentsTileList = [];

        if (sizeof($counteragentsList)) {
            $groups = $this->db->query(
                '
                    WITH res AS (
                         SELECT cc.counteragent_id
                              , COUNT(DISTINCT cc.contract_id) AS contract_count
                         FROM pm.contract_counteragent AS cc
                         WHERE cc.contract_counteragent_role_id = :rid
                         GROUP BY cc.counteragent_id
                    )
                    SELECT ca.counteragent_id
                         , ca.counteragent_title
                         , res.contract_count
                    FROM res
                       , pm.counteragent AS ca
                    WHERE ca.counteragent_id = res.counteragent_id
                      AND ca.counteragent_id IN ('.implode(',', $counteragentsList).')
                    ORDER BY ca.counteragent_title            
                ',
                [
                    'rid' => $roleId
                ]
            );

            while($row = $groups->fetch()) {
                $counteragentsTileList[] = [
                    'type'  => 'text',
                    'value' => (int)$row['counteragent_id'],
                    'parent' => (int)$row['counteragent_id'],
                    'title' => $row['counteragent_title'],
                    'descr' => $row['counteragent_title'],
                    'selected' => 0,
                    'counts' => [
                        ['title' => 'Количество контрактов: ', 'icon' => 'rules', 'count' => $row['contract_count']]
                    ]
                ];
            }
        }

        return $counteragentsTileList;
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
                     , res.contract_count AS contract_count
                FROM (
                         SELECT m.member_id
                              , m.member_nick
                              , m.member_gender
                              , COUNT(DISTINCT c.contract_id) AS contract_count
                         FROM pm.contract AS c
                            , public.members AS m
                         WHERE m.member_id = c.responsible_member_id
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
                'contract_count' => $row['contract_count']
            ];
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'html' => $this->view->getPartial('contracts/search_engine/extend_manager_list', ['list' => $managersList])
        ]);
    }

    private function searchExtendGroupTiles() {
        $activeMap = $this->searchEngineParseActiveInt();

        $groups = $this->db->query(
            '
                WITH res AS (
                         SELECT gr.parent_id
                              , gl.group_title
                              , count(*) AS contract_count
                         FROM pm.contract AS c
                            , public.members AS m
                            , public.groups_relationships AS gr
                            , public.groups_langs AS gl
                         WHERE m.member_id = c.responsible_member_id
                           AND gr.group_id = m.member_group
                           AND gl.group_id = gr.parent_id
                           AND gl.lang_id = 1
                         GROUP BY gr.parent_id
                                , gl.group_title
                     )
                SELECT g.group_id AS node_id
                     , COALESCE(g.group_parent_id, 0) AS parent_id
                     , res.group_title AS title
                     , res.contract_count
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
                    ['title' => 'Количество контрактов: ', 'icon' => 'rules', 'count' => $row['contract_count']]
                ]
            ];
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'html' => $this->view->getPartial('contracts/search_engine/extend_tree_map', ['map' => $groupsMap])
        ]);
    }

    private function searchExtendCounterAgentsTiles($roleId) {
        $activeMap = $this->searchEngineParseActiveInt();

        $counterAgents = $this->db->query(
            '
                WITH res AS (
                         SELECT cc.counteragent_id
                              , COUNT(*) AS contract_count
                         FROM pm.contract_counteragent AS cc
                         WHERE cc.contract_counteragent_role_id = :rid
                         GROUP BY cc.counteragent_id
                     )
                SELECT c.counteragent_title  
                     , res.counteragent_id
                     , res.contract_count
                FROM res
                   , pm.counteragent AS c
                WHERE c.counteragent_id = res.counteragent_id
                ORDER BY c.counteragent_title            
            ',
            [
                'rid' => $roleId
            ]
        );

        $counterAgentsList = [];

        while ($row = $counterAgents->fetch()) {
            $row['selected'] = isset($activeMap[(int)$row['counteragent_id']]);

            $counterAgentsList[] = $row;
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'html' => $this->view->getPartial('contracts/search_engine/extend_counteragent_list', ['list' => $counterAgentsList])
        ]);
    }

    protected function apiContract_add() {
        $contractData = json_decode($this->request->getPost('data'), true);

        /*print_r([
            'mid' => $this->user->getId(),
            'tid' => 1, // Договор оказания услуг
            'tit' => trim($contractData['meta']['title']),
            'num' => trim($contractData['meta']['number']),
            'rid' => (int)$contractData['meta']['responsible_id'],
            'pid' => (int)$contractData['meta']['prolongation'],
            'did' => (int)$contractData['meta']['prolongation_duration'],
            'eid' => (int)$contractData['meta']['term'],
            'bef' => (int)$contractData['meta']['pay_before'],
            'date' => $contractData['meta']['date'].' 23:59:59',
            'sign' => $contractData['meta']['sign'] ? $contractData['meta']['sign'].' 23:59:59' : null,
            'sig' => json_encode($contractData['signers'])
        ]);
        exit;*/

        $contractId = (int)$this->db->query(
            '
                SELECT pm.contract_create(
                       :mid
                     , :tid
                     , :tit
                     , :num
                     , :rid
                     , :pid
                     , :did
                     , :eid
                     , :bef
                     , :cid
                     , :date
                     , :sign
                     , :sig::jsonb
                     ) AS res
            ',
            [
                'mid' => $this->user->getId(),
                'tid' => 1, // Договор оказания услуг
                'tit' => trim($contractData['meta']['title']),
                'num' => trim($contractData['meta']['number']),
                'rid' => (int)$contractData['meta']['responsible_id'],
                'pid' => (int)$contractData['meta']['prolongation'],
                'did' => (int)$contractData['meta']['prolongation_duration'],
                'eid' => (int)$contractData['meta']['term'],
                'bef' => (int)$contractData['meta']['pay_before'],
                'cid' => (int)$contractData['meta']['currency'],
                'date' => $contractData['meta']['date'].' 23:59:59',
                'sign' => $contractData['meta']['sign'] ? $contractData['meta']['sign'].' 23:59:59' : null,
                'sig' => json_encode($contractData['signers'])
            ]
        )->fetch()['res'];

        $contractData = (new ContractsHandler())->counterAgentContracts(0, $contractId)[0];

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $contractId,
            'html' => $this->view->getPartial('contracts/partials/contract_td', $contractData)
        ]);
    }

    protected function apiDictionaries()
    {
        $this->sendResponseAjax([
            'state' => 'yes',
            'terms' => $this->db->query(
                '
                    SELECT pt.payment_term_id
                         , pt.term_title
                    FROM pm.payment_term AS pt
                    ORDER BY pt.term_title
                '
            )->fetchAll(),
            'currencies' => $this->db->query(
                '
                    SELECT c.currency_id
                         , c.currency_title
                    FROM common.currency AS c
                    ORDER BY c.currency_title
                '
            )->fetchAll(),
            'prolongations' => $this->db->query(
                '
                    SELECT cp.contract_prolongation_id
                         , cp.prolongation_title
                    FROM pm.contract_prolongation AS cp
                    ORDER BY cp.prolongation_title
                '
            )->fetchAll(),
            'durations' => $this->db->query(
                '
                    SELECT cpd.contract_prolongation_duration_id
                         , cpd.duration_month
                    FROM pm.contract_prolongation_duration AS cpd
                    WHERE cpd.duration_month <> 0  
                    ORDER BY cpd.duration_month
                '
            )->fetchAll()
        ]);
    }

    protected function apiContract_search()
    {
        $counteragentId = (int)$this->request->getPost('counteragent_id');
        $number = '%'.trim(mb_strtolower($this->request->getPost('number'))).'%';
        $roleId = (int)$this->request->getPost('role');

        $whereList = [];
        $whereArgs = [];

        if ($counteragentId) {
            $whereList[] = ' AND cc.counteragent_id = :cid';
            $whereArgs['cid'] = $counteragentId;
        }

        if ($number) {
            $whereList[] = ' AND LOWER(ct.contract_number) LIKE :nmb';
            $whereArgs['nmb'] = $number;
        }

        if ($roleId) {
            $whereList[] = ' AND cc.contract_counteragent_role_id = :rid';
            $whereArgs['rid'] = $roleId;
        }

        $contracts = $this->db->query(
            '
                SELECT ct.contract_id
                     , ct.contract_number
                     , ct.contract_sign_date::date
                     , json_agg(json_build_object(
                         \'role\', cr.role_name, 
                         \'role_title\', cr.role_title, 
                         \'counteragent_id\', cc.counteragent_id, 
                         \'counteragent_title\', c.counteragent_title
                       )) AS contract_counteragent
                FROM pm.contract AS ct
                   , pm.contract_counteragent AS cc
                   , pm.contract_counteragent_role AS cr
                   , pm.counteragent AS c
                WHERE cc.contract_id = ct.contract_id
                  AND cr.contract_counteragent_role_id = cc.contract_counteragent_role_id
                  AND c.counteragent_id = cc.counteragent_id
                  '.implode(' ', $whereList).'
                GROUP BY ct.contract_id
                       , ct.contract_number
                       , ct.contract_sign_date::date
                ORDER BY ct.contract_number
                LIMIT 30
            ',
            $whereArgs
        );

        $contractsList = [];

        while($row = $contracts->fetch()) {
            $row['contract_counteragent'] = json_decode($row['contract_counteragent'], true);

            $contractsList[] = $row;
        }


        $this->sendResponseAjax([
            'state' => 'yes',
            'contracts' => $contractsList
        ]);
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

            if ($tileUpdate == 'responsible') {
                $responsibleTileList = $this->searchEngineManagerPrepareMenuTiles($filtersMap['responsible'] ?? []);

                $tilesToUpdate['responsible'] = '';

                for($a = 0, $len = sizeof($responsibleTileList); $a < $len; $a++) {
                    $tilesToUpdate['responsible'] .= $this->view->getPartial(
                        'partials/eliza_search/tiles/user',
                        $responsibleTileList[$a]
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
            else if ($tileUpdate == 'customer' || $tileUpdate == 'executor') {
                $counterAgentsTileList = $this->searchEngineCounterAgentsPrepareMenuTiles($tileUpdate == 'customer' ? 2 : 1, $filtersMap[$tileUpdate] ?? []);

                $tilesToUpdate[$tileUpdate] = '';

                for($a = 0, $len = sizeof($counterAgentsTileList); $a < $len; $a++) {
                    $tilesToUpdate[$tileUpdate] .= $this->view->getPartial(
                        'partials/eliza_search/tiles/text',
                        $counterAgentsTileList[$a]
                    );
                }
            }
            $this->sendResponseAjax([
                'state' => 'yes',
                'content' => $this->view->getPartial('contracts/partials/search_table'),
                'pagination' => $result['pagination'],
                'extend_tiles' => $tilesToUpdate
            ]);
        }
        else if ($act == 'extend') {
            if ($subAct == 'tiles') {
                if ($name == 'responsible') {
                    $this->searchExtendManagerTiles();
                }
                else if ($name == 'group') {
                    $this->searchExtendGroupTiles();
                }
                else if ($name == 'customer' || $name == 'executor') {
                    $this->searchExtendCounterAgentsTiles($name == 'customer' ? 2 : 1);
                }
            }

        }
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
                'tid' => 2,
                'pub' => $public,
                'tit' => $title,
                'uri' => $uri,
            ]
        );

        $this->sendResponseAjax([
            'state' => 'yes'
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
                WHERE l.link_type_id = 2
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

    protected function apiContact_change() {
        $counterAgentId = (int)$this->request->getPost('counteragent_id');
        $contactId = (int)$this->request->getPost('contact_id');
        $contractId = (int)$this->request->getPost('contract_id');

        $this->db->query(
            ' SELECT pm.contract_counteragent_contact_update(:cid, :aid, :tid)',
            [
                'cid' => $contractId,
                'aid' => $counterAgentId,
                'tid' => $contactId
            ]
        );

        $this->sendResponseAjax([
            'state' => 'yes',
            'html' => $this->view->getPartial(
                'contracts/partials/contract_edit_signer',
                $this->db->query(
                    '
                        SELECT ccr.contract_counteragent_role_id
                             , ccr.role_title
                             , c.counteragent_id
                             , c.counteragent_title
                             , COALESCE(ct.contact_id, 0) AS contact_id
                             , COALESCE(ct.contact_nickname, \'\') AS contact_nickname
                             , COALESCE(ctr.role_title, \'\') AS contact_role_title
                             , c.contact_node_id
                        FROM pm.contract_counteragent AS cc
                             CROSS JOIN pm.contract_counteragent_role AS ccr
                             LEFT JOIN pm.contract_counteragent_contact AS ccc
                                  ON   ccc.contract_id = cc.contract_id
                                   AND ccc.counteragent_id = cc.counteragent_id
                             LEFT JOIN pm.contact AS ct
                                  ON   ct.contact_id = ccc.contact_id
                             LEFT JOIN pm.contact_role AS ctr 
                                  ON   ctr.contact_role_id = ct.contact_role_id
                           , pm.counteragent AS c
                        WHERE cc.contract_id = :cid
                          AND ccr.contract_counteragent_role_id = cc.contract_counteragent_role_id
                          AND c.counteragent_id = cc.counteragent_id
                          AND c.counteragent_id = :aid
                        ORDER BY ccr.role_title    
                    ',
                    [
                        'cid' => $contractId,
                        'aid' => $counterAgentId
                    ]
                )->fetch()
            )
        ]);

    }
}