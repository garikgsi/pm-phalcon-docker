<?php
namespace Site\Front\Handlers;


use Phalcon\Di\Injectable;

class ContractsHandler extends Injectable
{
    private array $stmtObject = [];
    private array $stmtWhere = [];
    private array $requestUri = [];
    private array $filterMap = [];

    public function __construct()
    {

    }

    public function counterAgentContracts(int $counterAgentId, int $contractId = 0) : array
    {
        $contractsList = [];

        $contracts = $this->db->query(
            '
                WITH cts AS (
                         SELECT DISTINCT cc.contract_id
                         FROM pm.contract_counteragent AS cc
                         WHERE '.($contractId ? ' cc.contract_id = '.$contractId : 'cc.counteragent_id = '.$counterAgentId).'
                     )
                   , cntr AS (
                         SELECT cts.contract_id
                              , json_object_agg(
                                  ccr.contract_counteragent_role_id,
                                    (\'{\'||\'"role_title":\' || to_json(ccr.role_title::text)||\',\'
                                      ||\'"counteragent_id":\' || to_json(c.counteragent_id::text)||\',\'
                                      ||\'"counteragent_title":\' || to_json(c.counteragent_title::text)||\',\'
                                      ||\'"contact_id":\' || to_json(COALESCE(ct.contact_id::text, \'0\')::text)||\',\'
                                      ||\'"contact_nickname":\' || to_json(COALESCE(ct.contact_nickname::text, \'\')::text)||\',\'
                                      ||\'"contact_node_id":\' || to_json(c.contact_node_id::text)||\'\'
                                      ||
                                   \'}\'
                                  )::jsonb) AS agent_map
                         FROM cts
                            , pm.contract_counteragent AS cc
                              CROSS JOIN pm.contract_counteragent_role AS ccr
                              LEFT JOIN pm.contract_counteragent_contact AS ccc
                                   ON   ccc.contract_id = cc.contract_id
                                    AND ccc.counteragent_id = cc.counteragent_id
                              LEFT JOIN pm.contact AS ct
                                   ON   ct.contact_id = ccc.contact_id
                            , pm.counteragent AS c
                         WHERE cc.contract_id = cts.contract_id
                           AND ccr.contract_counteragent_role_id = cc.contract_counteragent_role_id
                           AND c.counteragent_id = cc.counteragent_id
                         GROUP BY cts.contract_id
                     )
                SELECT c.contract_id
                     , c.contract_number
                     , c.contract_title
                     , c.contract_finish_date
                     , c.contract_sign_date
                     , cs.contract_status_id
                     , cs.status_title
                     , cr.currency_id
                     , cr.currency_title
                     , cp.contract_prolongation_id
                     , cp.prolongation_title
                     , cpd.duration_month
                     , pt.payment_term_id
                     , pt.term_title
                     , c.payment_before
                     , cntr.agent_map
                     , c.create_member_id
                     , m.member_nick AS create_member_nick
                     , c.responsible_member_id
                     , mr.member_nick AS responsible_member_nick
                FROM cts
                   , cntr
                   , pm.contract AS c
                   , pm.contract_status AS cs
                   , common.currency AS cr
                   , pm.contract_prolongation AS cp
                   , pm.contract_prolongation_duration AS cpd
                   , pm.payment_term AS pt
                   , public.members AS m
                   , public.members AS mr
                WHERE cntr.contract_id = cts.contract_id
                  AND c.contract_id = cts.contract_id
                  AND cs.contract_status_id = c.contract_status_id
                  AND cr.currency_id = c.currency_id
                  AND cp.contract_prolongation_id = c.contract_prolongation_id
                  AND cpd.contract_prolongation_duration_id = c.contract_prolongation_duration_id
                  AND pt.payment_term_id = c.payment_term_id                
                  AND m.member_id = c.create_member_id
                  AND mr.member_id = c.responsible_member_id
            '
        );

        while($row = $contracts->fetch()) {
            $row['agent_map'] = json_decode($row['agent_map'], true);

            $contractsList[]= $row;
        }

        return $contractsList;
    }
    
    public function searchPrepareParams()
    {
        $this->stmtObject = [];
        $this->stmtWhere = [];

        $contractStatus = trim($this->request->get('status'));
        $contractNumber = trim($this->request->get('number') ?? '');
        $contractTitle = trim($this->request->get('title') ?? '');
        $onlyMine = (int)($this->request->get('only_mine') ?? 0);
        $customersList = $this->request->get('customer') ? explode(',', $this->request->get('customer')) : [];
        $executorsList = $this->request->get('executor') ? explode(',', $this->request->get('executor')) : [];
        $responsiblesList = $this->request->get('responsible') ? explode(',', $this->request->get('responsible')) : [];
        $groupsList = $this->request->get('group') ? explode(',', $this->request->get('group')) : [];


        if (sizeof($customersList)) {
            foreach($customersList as $index => $val) {
                $customersList[$index] = (int)$val;
            }

            $this->stmtWhere[] = 'ct.contract_id IN (
                                SELECT cc1.contract_id
                                FROM pm.contract_counteragent AS cc1 
                                WHERE cc1.counteragent_id IN ('.implode(',', $customersList).')
                                  AND cc1.contract_counteragent_role_id = 2
                            )
                           ';

            $this->requestUri[] = 'customer='.implode(',', $customersList);
            $this->filterMap['customer'] = $customersList;
        }

        if (sizeof($executorsList)) {
            foreach($executorsList as $index => $val) {
                $executorsList[$index] = (int)$val;
            }

            $this->stmtWhere[] = 'ct.contract_id IN (
                                SELECT cc2.contract_id
                                FROM pm.contract_counteragent AS cc2 
                                WHERE cc2.counteragent_id IN ('.implode(',', $executorsList).')
                                  AND cc2.contract_counteragent_role_id = 1
                            )
                           ';

            $this->requestUri[] = 'executor='.implode(',', $executorsList);
            $this->filterMap['executor'] = $executorsList;
        }

        if (sizeof($responsiblesList)) {
            foreach($responsiblesList as $index => $val) {
                $responsiblesList[$index] = (int)$val;
            }

            $this->stmtWhere[] = 'ct.responsible_member_id IN ('.implode(',', $responsiblesList).')';

            $this->requestUri[] = 'responsible='.implode(',', $responsiblesList);
            $this->filterMap['responsible'] = $responsiblesList;
        }

        if (sizeof($groupsList)) {
            foreach($groupsList as $index => $val) {
                $groupsList[$index] = (int)$val;
            }

            $this->stmtWhere[] = 'ct.responsible_member_id IN (
                                SELECT m.member_id
                                FROM public.members AS m
                                    , public.groups_relationships AS grl 
                                WHERE grl.parent_id IN ('.implode(',', $groupsList).')    
                                  AND m.member_group = grl.group_id 
                            )
                           ';

            $this->requestUri[] = 'group='.implode(',', $groupsList);
            $this->filterMap['group'] = $groupsList;
        }
        
        $filterProlongation = explode('_', $this->request->get('prolongation'));

        if (sizeof($filterProlongation) == 2 && $filterProlongation[0] == 'prolongation') {
            $prolongationId = (int)$filterProlongation[1];
            $this->stmtWhere[] = 'ct.contract_prolongation_id = '.$prolongationId;

            $this->requestUri[] = 'prolongation=prolongation_'.$prolongationId;

            $this->filterMap['prolongation'] = $prolongationId;
        }

        if ($contractStatus) {
            $statuses = explode(',', $contractStatus);

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
                $this->requestUri[] = 'status='.implode(',', $statusesReq);
                $this->stmtWhere[] = 'cs.status_name IN ('.implode(',', $statusesList).')';

                $this->filterMap['status'] = $statusesMap;
            }
        }

        if ($onlyMine) {
            $this->stmtWhere[] = 'ct.responsible_member_id = :mid';
            $this->stmtObject['mid'] = $this->user->getId();

            $this->requestUri[] = 'only_mine=1';
            $this->filterMap['only_mine'] = 1;
        }

        if ($contractNumber) {
            $this->stmtWhere[] = 'LOWER(ct.contract_number) LIKE :num';
            $this->stmtObject['num'] = '%'.$contractNumber.'%';

            $this->requestUri[] = 'number='.$contractNumber;
            $this->filterMap['number'] = $contractNumber;
        }

        if ($contractTitle) {
            $this->stmtWhere[] = 'LOWER(ct.contract_title) LIKE :tit';
            $this->stmtObject['tit'] = '%'.$contractTitle.'%';

            $this->requestUri[] = 'title='.$contractTitle;
            $this->filterMap['title'] = $contractTitle;
        }
        
    }

    public function searchGetColumns() : array
    {
        return [
            'id' => [
                'name' => 'id',
                'column' => 'id',
                'title' => 'ID',
                'order' => 0,
                'active' => 1,
                'sql' => 'contract_id'
            ],
            'number' => [
                'name' => 'number',
                'column' => 'number',
                'title' => 'Номер договора',
                'order' => 1,
                'active' => 1,
                'sql' => 'contract_number'
            ],
            'status' => [
                'name' => 'status',
                'column' => 'status',
                'title' => 'Статус',
                'order' => 2,
                'active' => 1,
                'sql' => 'contract_status_id'
            ],
            'prolongation' => [
                'name' => 'prolongation',
                'column' => 'prolongation',
                'title' => 'Пролонгация',
                'order' => 3,
                'active' => 1,
                'sql' => 'contract_prolongation_id'
            ],
            'payment' => [
                'name' => 'payment',
                'column' => 'payment',
                'title' => 'Условия оплаты',
                'order' => 4,
                'active' => 1,
                'sql' => 'payment_term_id'
            ],
            'currency' => [
                'name' => 'currency',
                'column' => 'currency',
                'title' => 'Валюта',
                'order' => 5,
                'active' => 0,
                'sql' => 'currency_id'
            ],
            'customer' => [
                'name' => 'customer',
                'column' => 'counteragent',
                'title' => 'Заказчик',
                'order' => 6,
                'active' => 1,
                'sql' => 'customer'
            ],
            'executor' => [
                'name' => 'executor',
                'column' => 'counteragent',
                'title' => 'Исполнитель',
                'order' => 7,
                'active' => 1,
                'sql' => 'executor'
            ],
            'finish' => [
                'name' => 'finish',
                'column' => 'base',
                'title' => 'Срок действия',
                'order' => 8,
                'active' => 1,
                'sql' => 'contract_finish_date'
            ],
            'sign' => [
                'name' => 'sign',
                'column' => 'sign',
                'title' => 'Дата подписания',
                'order' => 9,
                'active' => 1,
                'sql' => 'contract_sign_date'
            ],
            'responsible' => [
                'name' => 'responsible',
                'column' => 'user',
                'title' => 'Отвественный',
                'order' => 10,
                'active' => 1,
                'sql' => 'responsible_member_nick'
            ],
            'author' => [
                'name' => 'author',
                'column' => 'user',
                'title' => 'Автор',
                'order' => 11,
                'active' => 1,
                'sql' => 'create_member_nick'
            ]
        ];
    }

    public function searchGetSorts() : array
    {
        return [
            'id' => [
                'name' => 'id',
                'title' => 'ID',
                'order' => 100,
                'selected' => 0,
                'mode' => '',
                'sql' => 'c.contract_id'
            ],
            'number' => [
                'name' => 'number',
                'title' => 'Номер договора',
                'order' => 101,
                'selected' => 0,
                'mode' => '',
                'sql' => 'c.contract_number'
            ],
            'status' => [
                'name' => 'status',
                'title' => 'Статус',
                'order' => 102,
                'selected' => 0,
                'mode' => '',
                'sql' => 'c.contract_status_id'
            ],
            'prolongation' => [
                'name' => 'prolongation',
                'title' => 'Пролонгация',
                'order' => 103,
                'selected' => 0,
                'mode' => '',
                'sql' => 'c.contract_prolongation_id'
            ],
            'payment' => [
                'name' => 'payment',
                'title' => 'Условия оплаты',
                'order' => 104,
                'selected' => 0,
                'mode' => '',
                'sql' => 'pt.payment_term_id'
            ],
            'currency' => [
                'name' => 'currency',
                'title' => 'Валюта',
                'order' => 105,
                'selected' => 0,
                'mode' => '',
                'sql' => 'cr.currency_id'
            ],
            'customer' => [
                'name' => 'customer',
                'title' => 'Заказчик',
                'order' => 106,
                'selected' => 0,
                'mode' => '',
                'sql' => 'cntr.agent_map::jsonb#>\'{2,counteragent_title}\''
            ],
            'executor' => [
                'name' => 'executor',
                'title' => 'Исполнитель',
                'order' => 107,
                'selected' => 0,
                'mode' => '',
                'sql' => 'cntr.agent_map::jsonb#>\'{1,counteragent_title}\''
            ],
            'finish' => [
                'name' => 'finish',
                'title' => 'Срок действия',
                'order' => 108,
                'selected' => 0,
                'mode' => '',
                'sql' => 'c.contract_finish_date'
            ],
            'sign' => [
                'name' => 'sign',
                'title' => 'Дата подписания',
                'order' => 109,
                'selected' => 0,
                'mode' => '',
                'sql' => 'c.contract_sign_date'
            ],
            'responsible' => [
                'name' => 'responsible',
                'title' => 'Ответственный',
                'order' => 110,
                'selected' => 0,
                'mode' => '',
                'sql' => 'mr.member_nick'
            ],
            'author' => [
                'name' => 'author',
                'title' => 'Автор',
                'order' => 111,
                'selected' => 0,
                'mode' => '',
                'sql' => 'm.member_nick'
            ]
        ];
    }

    public function searchGetRequestUri() : array
    {
        return $this->requestUri;
    }

    public function searchGetFilterMap() : array
    {
        return $this->filterMap;
    }

    public function searchGetTotalCount() : int
    {
        return (int)$this->db->query(
            '
                SELECT COUNT(*) AS total
                FROM pm.contract AS ct
                    , pm.contract_status AS cs
                WHERE cs.contract_status_id = ct.contract_status_id
                '.(sizeof($this->stmtWhere) ? ' AND '.implode(' AND ', $this->stmtWhere) : '').'
            ',
            $this->stmtObject
        )->fetch()['total'];
    }

    public function searchPerform($itemsPerPage, $sqlOrder = '', $sqlOffset = '') : array
    {
        $contracts = $this->db->query(
            '
                WITH cts AS (
                         SELECT ct.contract_id
                         FROM pm.contract AS ct
                            , pm.contract_status AS cs
                         WHERE cs.contract_status_id = ct.contract_status_id
                         '.(sizeof($this->stmtWhere) ? ' AND '.implode(' AND ', $this->stmtWhere) : '').'
                     )
                   , cntr AS (
                         SELECT cts.contract_id
                              , json_object_agg(
                                  ccr.contract_counteragent_role_id,
                                    (\'{\'||\'"role_title":\' || to_json(ccr.role_title::text)||\',\'
                                      ||\'"counteragent_id":\' || to_json(c.counteragent_id::text)||\',\'
                                      ||\'"counteragent_title":\' || to_json(c.counteragent_title::text)||\',\'
                                      ||\'"contact_id":\' || to_json(COALESCE(ct.contact_id::text, \'0\')::text)||\',\'
                                      ||\'"contact_nickname":\' || to_json(COALESCE(ct.contact_nickname::text, \'\')::text)||\',\'
                                      ||\'"contact_node_id":\' || to_json(c.contact_node_id::text)||\'\'
                                      ||
                                   \'}\'
                                  )::jsonb) AS agent_map
                         FROM cts
                            , pm.contract_counteragent AS cc
                              CROSS JOIN pm.contract_counteragent_role AS ccr
                              LEFT JOIN pm.contract_counteragent_contact AS ccc
                                   ON   ccc.contract_id = cc.contract_id
                                    AND ccc.counteragent_id = cc.counteragent_id
                              LEFT JOIN pm.contact AS ct
                                   ON   ct.contact_id = ccc.contact_id
                            , pm.counteragent AS c
                         WHERE cc.contract_id = cts.contract_id
                           AND ccr.contract_counteragent_role_id = cc.contract_counteragent_role_id
                           AND c.counteragent_id = cc.counteragent_id
                         GROUP BY cts.contract_id
                     )
                SELECT c.contract_id
                     , c.contract_number
                     , c.contract_title
                     , c.contract_finish_date::date
                     , c.contract_sign_date::date
                     , cs.contract_status_id
                     , cs.status_title
                     , cr.currency_id
                     , cr.currency_title
                     , cp.contract_prolongation_id
                     , cpd.duration_month
                     , cp.prolongation_title
                     , pt.payment_term_id
                     , pt.term_title
                     , c.payment_before
                     , cntr.agent_map
                     , c.create_member_id
                     , m.member_nick AS create_member_nick
                     , c.responsible_member_id
                     , mr.member_nick AS responsible_member_nick
                FROM cts
                   , cntr
                   , pm.contract AS c
                   , pm.contract_status AS cs
                   , common.currency AS cr
                   , pm.contract_prolongation AS cp
                   , pm.contract_prolongation_duration AS cpd
                   , pm.payment_term AS pt
                   , public.members AS m
                   , public.members AS mr
                WHERE cntr.contract_id = cts.contract_id
                  AND c.contract_id = cts.contract_id
                  AND cs.contract_status_id = c.contract_status_id
                  AND cr.currency_id = c.currency_id
                  AND cp.contract_prolongation_id = c.contract_prolongation_id
                  AND cpd.contract_prolongation_duration_id = c.contract_prolongation_duration_id
                  AND pt.payment_term_id = c.payment_term_id                
                  AND m.member_id = c.create_member_id
                  AND mr.member_id = c.responsible_member_id
                '.$sqlOrder.'
                LIMIT '.$itemsPerPage.'
                '.$sqlOffset.'
            ',
            $this->stmtObject
        );

        return $contracts->fetchAll();
    }

    public function contractsSearch(array $searchParams) : array
    {

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
                'sql' => 'c.contract_id'
            ],
            'number' => [
                'name' => 'number',
                'title' => 'Номер договора',
                'order' => 101,
                'selected' => 0,
                'mode' => '',
                'sql' => 'c.contract_number'
            ],
            'status' => [
                'name' => 'status',
                'title' => 'Статус',
                'order' => 102,
                'selected' => 0,
                'mode' => '',
                'sql' => 'cs.status_title'
            ],
            'prolongation' => [
                'name' => 'prolongation',
                'title' => 'Пролонгация',
                'order' => 103,
                'selected' => 0,
                'mode' => '',
                'sql' => 'cp.prolongation_title'
            ]
        ];

        $stmtObject = [];
        $stmtWhere = [];

        $contractProlongation = (int)($searchParams['prolongation'] ?? 0);
        $contractStatus = (int)($searchParams['status'] ?? 0);
        $contractNumber = trim($searchParams['number'] ?? '');
        $contractTitle = trim($searchParams['title'] ?? '');
        $onlyMine = (int)($searchParams['only_mine'] ?? 0);
        $customersList = $searchParams['customers'] ?? [];
        $executorsList = $searchParams['executors'] ?? [];
        $responsiblesList = $searchParams['responsibles'] ?? [];
        $departmentsList = $searchParams['departments'] ?? [];


        if (sizeof($customersList)) {
            foreach($customersList as $index => $val) {
                $customersList[$index] = (int)$val;
            }

            $stmtWhere[] = 'ct.contract_id IN (
                                SELECT cc1.contract_id
                                FROM pm.contract_counteragent AS cc1 
                                WHERE cc1.counteragent_id IN ('.implode(',', $customersList).')
                                  AND cc1.contract_counteragent_role_id = 2
                            )
                           ';
        }

        if (sizeof($executorsList)) {
            foreach($executorsList as $index => $val) {
                $executorsList[$index] = (int)$val;
            }

            $stmtWhere[] = 'ct.contract_id IN (
                                SELECT cc2.contract_id
                                FROM pm.contract_counteragent AS cc2 
                                WHERE cc2.counteragent_id IN ('.implode(',', $executorsList).')
                                  AND cc2.contract_counteragent_role_id = 1
                            )
                           ';
        }

        if (sizeof($responsiblesList)) {
            foreach($responsiblesList as $index => $val) {
                $responsiblesList[$index] = (int)$val;
            }

            $stmtWhere[] = 'ct.responsible_member_id IN ('.implode(',', $responsiblesList).')';
        }

        if (sizeof($departmentsList)) {
            foreach($departmentsList as $index => $val) {
                $departmentsList[$index] = (int)$val;
            }

            $stmtWhere[] = 'ct.responsible_member_id IN (
                                SELECT m.member_id
                                FROM public.members AS m
                                    , public.groups_relationships AS grl 
                                WHERE grl.parent_id IN ('.implode(',', $departmentsList).')    
                                  AND m.member_group = grl.group_id 
                            )
                           ';
        }


        if ($contractProlongation) {
            $stmtWhere[] = 'ct.contract_prolongation_id = :pid';
            $stmtObject['pid'] = $contractProlongation;
        }

        if ($contractStatus) {
            $stmtWhere[] = 'ct.contract_status_id = :sid';
            $stmtObject['sid'] = $contractStatus;
        }

        if ($onlyMine) {
            $stmtWhere[] = 'ct.responsible_member_id = :mid';
            $stmtObject['mid'] = $this->user->getId();
        }

        if ($contractNumber) {
            $stmtWhere[] = 'ct.contract_number LIKE :num';
            $stmtObject['num'] = '%'.$contractNumber.'%';
        }

        if ($contractTitle) {
            $stmtWhere[] = 'ct.contract_title LIKE :tit';
            $stmtObject['tit'] = '%'.$contractTitle.'%';
        }


        $totalCount = (int)$this->db->query(
            '
                SELECT COUNT(*) AS total
                FROM pm.contract AS ct
                '.(sizeof($stmtWhere) ? ' WHERE '.implode(' AND ', $stmtWhere) : '').'
            '
        )->fetch()['total'];

        $this->db->query(
            '
                WITH cts AS (
                         SELECT ct.contract_id
                         FROM pm.contract AS ct
                         '.(sizeof($stmtWhere) ? ' WHERE '.implode(' AND ', $stmtWhere) : '').'
                     )
                   , cntr AS (
                         SELECT cts.contract_id
                              , json_object_agg(
                                  ccr.contract_counteragent_role_id,
                                    (\'{\'||\'"role_title":\' || to_json(ccr.role_title::text)||\',\'
                                      ||\'"counteragent_id":\' || to_json(c.counteragent_id::text)||\',\'
                                      ||\'"counteragent_title":\' || to_json(c.counteragent_title::text)||\',\'
                                      ||\'"contact_id":\' || to_json(COALESCE(ct.contact_id::text, \'0\')::text)||\',\'
                                      ||\'"contact_nickname":\' || to_json(COALESCE(ct.contact_nickname::text, \'\')::text)||\',\'
                                      ||\'"contact_node_id":\' || to_json(c.contact_node_id::text)||\'\'
                                      ||
                                   \'}\'
                                  )::jsonb) AS agent_map
                         FROM cts
                            , pm.contract_counteragent AS cc
                            , pm.contract_counteragent_role AS ccr
                            , pm.contract_counteragent_contact AS ccc
                            , pm.counteragent AS c
                            , pm.contact AS ct
                         WHERE cc.contract_id = cts.contract_id
                           AND ccr.contract_counteragent_role_id = cc.contract_counteragent_role_id
                           AND ccr.contract_counteragent_role_id = cc.contract_counteragent_role_id
                           AND ccc.contract_id = cc.contract_id
                           AND ccc.counteragent_id = cc.counteragent_id
                           AND c.counteragent_id = cc.counteragent_id
                           AND ct.contact_id = ccc.contact_id
                         GROUP BY cts.contract_id
                     )
                SELECT c.contract_id
                     , c.contract_number
                     , c.contract_title
                     , c.contract_finish_date
                     , c.contract_sign_date
                     , cs.contract_status_id
                     , cs.status_title
                     , cp.contract_prolongation_id
                     , cp.prolongation_title
                     , pt.payment_term_id
                     , pt.term_title
                     , c.payment_before
                     , cntr.agent_map
                     , c.create_member_id
                     , m.member_nick AS create_member_nick
                     , c.responsible_member_id
                     , mr.member_nick AS responsible_member_nick
                FROM cts
                   , cntr
                   , pm.contract AS c
                   , pm.contract_status AS cs
                   , pm.contract_prolongation AS cp
                   , pm.payment_term AS pt
                   , public.members AS m
                   , public.members AS mr
                WHERE cntr.contract_id = cts.contract_id
                  AND c.contract_id = cts.contract_id
                  AND cs.contract_status_id = c.contract_status_id
                  AND cp.contract_prolongation_id = c.contract_prolongation_id
                  AND pt.payment_term_id = c.payment_term_id                
                  AND m.member_id = c.create_member_id
                  AND mr.member_id = c.responsible_member_id
            ',
            $stmtObject
        );

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
}