<?php
namespace Site\Front\Controllers;


use Core\Extender\ControllerApp;

/**
 * Class CounteragentsController
 * @package Site\Front\Controllers
 */
class InternationalSegmentsController extends ControllerApp
{
    public function initialize()
    {
        parent::initialize();

        $this->hapi->setHapiController('international_segments');
    }

    public function indexAction()
    {
        if ($this->request->isPost()) {
            $act = $this->request->get('act');

            if ($act == 'segment_create') {
                $this->segmentCreate();
            }
            else if ($act == 'segment_update') {
                $this->segmentUpdate();
            }
            else if ($act == 'segment_delete') {
                $this->segmentDelete();
            }

        }

        $this->view->setVar('mainMenuData', [
            'active' => 'pm_dict_international_segment',
            'overlay' => 1,
            'template' => 'partials/menu/menu_dicts',
            'icon' => 'table.svg',
            'title' => 'Международные сегменты',
        ]);

        $this->hapi->setHapiAction('index');

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
                ORDER BY isg.segment_title
            '
        );

        $segmentsMap = [];

        while ($row = $segments->fetch()) {
            $parentId = (int)$row['parent_international_segment_id'];

            $row['can_edit'] = 1;

            $segmentsMap[$parentId] = $segmentsMap[$parentId] ?? [];

            $segmentsMap[$parentId][] = $row;
        }

        /*echo '<pre>';
        print_r($segmentsMap);
        exit;*/

        $this->view->setVar('internationalSegmentsMap', $segmentsMap);
    }

    private function segmentCreate()
    {
        $parentId = (int)$this->request->getPost('parent_id');
        $title = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.international_segment_create(:sid, :mid, :title) AS res',
            [
                'sid' => $parentId,
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
                    -2 => 'Название занято',
                    -3 => 'Родитель не существует'
                ][$result]
            ]);
        }

        $segment = [
            'international_segment_id' => $result,
            'parent_international_segment_id' => $parentId,
            'segment_title' => $title,
            'counteragent_count' => 0,
            'list' => [],
            'map' => []
        ];

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result,
            'html' => $this->view->getPartial(
                'international_segments/partials/segment_item',
                $segment
            )
        ]);
    }

    private function segmentUpdate()
    {
        $id = (int)$this->request->get('id');
        $parentId = (int)$this->request->getPost('parent_id');
        $title = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.international_segment_update(:id, :sid, :mid, :title) AS res',
            [
                'id' => $id,
                'sid' => $parentId,
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
                    -2 => 'Название занято',
                    -3 => 'Родитель не существует'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);

    }

    private function segmentDelete()
    {
        $id = (int)$this->request->getPost('international_segment_id');

        $result = (int)$this->db->query(
            'SELECT pm.international_segment_delete(:id) AS res',
            ['id' => $id]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'У данного сегмента есть контрагенты',
                    -2 => 'У данного сегмента есть дочерние сегменты'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);
    }
}