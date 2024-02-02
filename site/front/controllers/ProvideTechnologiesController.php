<?php
namespace Site\Front\Controllers;


use Core\Extender\ControllerApp;

/**
 * Class CounteragentsController
 * @package Site\Front\Controllers
 */
class ProvideTechnologiesController extends ControllerApp
{
    public function initialize()
    {
        parent::initialize();

        $this->hapi->setHapiController('provide_technologies');
    }

    public function indexAction()
    {
        if ($this->request->isPost()) {
            $act = $this->request->get('act');

            if ($act == 'type_create') {
                $this->typeCreate();
            }
            else if ($act == 'type_update') {
                $this->typeUpdate();
            }
            else if ($act == 'type_delete') {
                $this->typeDelete();
            }
            else if ($act == 'tech_create') {
                $this->techCreate();
            }
            else if ($act == 'tech_update') {
                $this->techUpdate();
            }
            else if ($act == 'tech_delete') {
                $this->techDelete();
            }

        }

        $this->view->setVar('mainMenuData', [
            'active' => 'pm_dict_provide_technology',
            'overlay' => 1,
            'template' => 'partials/menu/menu_dicts',
            'icon' => 'table.svg',
            'title' => 'Технологии предоставления',
        ]);

        $this->hapi->setHapiAction('index');

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
                'can_edit' => 1,
                'counts' => [
                    'counteragents' => 0,
                    'technologies' => 0
                ],
                'list' => []
            ];
        }

        $technologies = $this->db->query(
            '
               SELECT pt.provide_technology_id
                    , pt.provide_technology_type_id
                    , pt.technology_title
                    , COUNT(DISTINCT cpt.provide_technology_id) AS counteragent_count
               FROM pm.provide_technology AS pt
                    LEFT JOIN pm.counteragent_provide_technology AS cpt 
                         ON   cpt.provide_technology_id = pt.provide_technology_id
               GROUP BY pt.provide_technology_id
                      , pt.provide_technology_type_id
                      , pt.technology_title
               ORDER BY pt.technology_title
            '
        );

        while ($row = $technologies->fetch()) {
            $row['can_edit'] = 1;

            $typesMap[$row['provide_technology_type_id']]['counts']['technologies']++;
            $typesMap[$row['provide_technology_type_id']]['counts']['counteragents'] += $row['counteragent_count'];
            $typesMap[$row['provide_technology_type_id']]['list'][] = $row;
        }

        $this->view->setVar('technologyTypesMap', $typesMap);
    }

    private function typeCreate()
    {
        $title = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.provide_technology_type_create(:mid, :title) AS res',
            [
                'mid' => $this->user->getId(),
                'title' => $title
            ]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Название не может быть пустым',
                    -2 => 'Название занято'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result,
            'html' => $this->view->getPartial(
                'provide_technologies/partials/tech_category',
                [
                    'provide_technology_type_id' => $result,
                    'type_title' => $title,
                    'counts' => [
                        'counteragents' => 0,
                        'technologies' => 0
                    ],
                    'list' => []
                ]
            )
        ]);
    }

    private function typeUpdate()
    {
        $id = (int)$this->request->get('id');
        $title = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.provide_technology_type_update(:id, :mid, :title) AS res',
            [
                'id' => $id,
                'mid' => $this->user->getId(),
                'title' => $title
            ]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Название не может быть пустым',
                    -2 => 'Название занято'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);
    }

    private function typeDelete()
    {
        $id = (int)$this->request->getPost('provide_technology_type_id');

        $result = (int)$this->db->query(
            'SELECT pm.provide_technology_type_delete(:id) AS res',
            ['id' => $id]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'У данного типа есть связанные технологии'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);
    }

    private function techCreate()
    {
        $techTypeId = trim($this->request->get('type'));
        $title = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.provide_technology_create(:tid, :mid, :title) AS res',
            [
                'tid' => $techTypeId,
                'mid' => $this->user->getId(),
                'title' => $title
            ]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Название не может быть пустым',
                    -2 => 'Указанный тип не существует',
                    -3 => 'Название занято'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result,
            'html' => $this->view->getPartial(
                'provide_technologies/partials/tech_item',
                [
                    'provide_technology_id' => $result,
                    'technology_title' => $title,
                    'counteragent_count' => 0
                ]
            )
        ]);
    }

    private function techUpdate()
    {
        $id = (int)$this->request->get('id');
        $title = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.provide_technology_update(:id, :mid, :title) AS res',
            [
                'id' => $id,
                'mid' => $this->user->getId(),
                'title' => $title
            ]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Название не может быть пустым',
                    -2 => 'Указанный тип не существует',
                    -3 => 'Название занято'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);
    }

    private function techDelete()
    {
        $id = (int)$this->request->get('provide_technology_id');

        $result = (int)$this->db->query(
            'SELECT pm.provide_technology_delete(:id) AS res',
            ['id' => $id]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Услуга связана с клиентами'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);
    }
}