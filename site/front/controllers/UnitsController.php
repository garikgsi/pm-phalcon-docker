<?php
namespace Site\Front\Controllers;


use Core\Extender\ControllerApp;

/**
 * Class UnitsController
 * @package Site\Front\Controllers
 */
class UnitsController extends ControllerApp
{
    public function initialize()
    {
        parent::initialize();

        $this->hapi->setHapiController('units');
    }

    public function indexAction()
    {
        if ($this->request->isPost()) {
            $act = $this->request->get('act');

            if ($act == 'group_create') {
                $this->groupCreate();
            }
            else if ($act == 'group_update') {
                $this->groupUpdate();
            }
            else if ($act == 'group_delete') {
                $this->groupDelete();
            }
            else if ($act == 'unit_create') {
                $this->unitCreate();
            }
            else if ($act == 'unit_update') {
                $this->unitUpdate();
            }
            else if ($act == 'unit_delete') {
                $this->unitDelete();
            }
            else if ($act == 'unit_lock') {
                $this->unitLock();
            }
            else if ($act == 'unit_sort') {
                $this->unitSort();
            }
            else if ($act == 'conversion_create') {
                $this->conversionCreate();
            }
            else if ($act == 'conversion_update') {
                $this->conversionUpdate();
            }
            else if ($act == 'conversion_delete') {
                $this->conversionDelete();
            }
        }

        $this->view->setVar('mainMenuData', [
            'active' => 'pm_dict_units',
            'overlay' => 1,
            'template' => 'partials/menu/menu_dicts',
            'icon' => 'design.svg',
            'title' => 'Единицы измерения',
        ]);

        $this->hapi->setHapiAction('index');

        $this->prepareUnitDictionaries();

        $groups = $this->db->query(
            '
                SELECT ug.unit_group_id
                     , ug.group_title
                FROM pm.unit_group AS ug
                ORDER BY ug.group_title
            '
        );

        $groupsMap = [];

        while($row = $groups->fetch()) {
            $row['units'] = [];
            $row['units_options'] = [[0, 'Выберите едницу измерения']];

            $groupsMap[$row['unit_group_id']] = $row;
        }

        $units = $this->db->query(
            '
                SELECT u.unit_id
                     , u.unit_group_id
                     , u.unit_title_full
                     , u.unit_title_short
                     , u.unit_type_id
                     , u.unit_lock
                     , u.conversion_count
                     , u.of_unit_id
                     , u.of_unit_title_short
                     , u.to_unit_id
                     , u.to_unit_title_short
                FROM pm.unit_vw AS u
                ORDER BY u.unit_order
            '
        );

        $unitsMap = [];

        while($row = $units->fetch()) {
            $row['conversions'] = [];

            $unitsMap[$row['unit_id']] = $row;

            $groupsMap[$row['unit_group_id']]['units'][] = &$unitsMap[$row['unit_id']];
            $groupsMap[$row['unit_group_id']]['units_options'][] = [(int)$row['unit_id'], $row['unit_title_short']];
        }

        $conversions = $this->db->query(
            '
                SELECT uc.unit_id
                     , uc.contain_unit_id
                     , uc.conversion_units
                FROM pm.unit_conversion AS uc
                   , pm.unit AS u
                WHERE uc.contain_unit_id = u.unit_id
                ORDER BY u.unit_order
            '
        );

        while($row = $conversions->fetch()) {
            $unitsMap[$row['unit_id']]['conversions'][] = $row;
        }

        $this->view->setVar('unitsGroupsMap', $groupsMap);
    }

    private function prepareUnitDictionaries() {
        $units = $this->db->query(
            '
                SELECT u.unit_id
                     , u.unit_title_short
                     , u.group_title
                FROM pm.unit_vw AS u
                WHERE u.unit_type_id = 1
                ORDER BY u.group_title
                       , u.unit_order            
            '
        );

        $unitsOptions = [[0, 'Не используется']];

        $unitsGroups = [];

        while($row = $units->fetch()) {
            if (!isset($unitsGroups[$row['group_title']])) {
                $unitsGroups[$row['group_title']] = [
                    'label' => $row['group_title'],
                    'list' => []
                ];

                $unitsOptions[] = &$unitsGroups[$row['group_title']];
            }

            $unitsGroups[$row['group_title']]['list'][] = [$row['unit_id'], $row['unit_title_short']];
        }

        $this->view->setVar('unitRatioOptions', $unitsOptions);

        $types = $this->db->query(
            '
                SELECT unit_type_id
                     , type_title
                FROM pm.unit_type
                ORDER BY unit_type_id
            '
        );

        $typeOptions = [];

        while($row = $types->fetch()) {
            $typeOptions[] = [(int)$row['unit_type_id'], $row['type_title']];
        }

        $this->view->setVar('unitTypeOptions', $typeOptions);
    }

    private function groupCreate() {
        $groupTitle = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.unit_group_create(:mid, :title) AS res',
            [
                'mid' => $this->user->getId(),
                'title' => $groupTitle
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
                'units/partials/unit_group',
                [
                    'unit_group_id' => $result,
                    'group_title' => $groupTitle,
                    'units' => []
                ]
            )
        ]);
    }

    private function groupUpdate() {
        $groupId = (int)$this->request->get('id');
        $groupTitle = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.unit_group_update(:cid, :mid, :title) AS res',
            [
                'cid' => $groupId,
                'mid' => $this->user->getId(),
                'title' => $groupTitle
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

    private function groupDelete() {
        $result = (int)$this->db->query(
            'SELECT pm.unit_group_delete(:id) AS res',
            ['id' => (int)$this->request->getPost('group_id')]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'В группе есть единицы измерения'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);

    }

    private function unitCreate() {
        $groupId = (int)$this->request->get('group_id');
        $unitTitle = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.unit_create(:mid, :gid, :title) AS res',
            [
                'mid' => $this->user->getId(),
                'gid' => $groupId,
                'title' => $unitTitle
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

        $this->prepareUnitDictionaries();

        $units = $this->db->query(
            '
                SELECT u.unit_id
                     , u.unit_title_short
                FROM pm.unit_vw AS u
                WHERE u.unit_group_id = (SELECT ut.unit_group_id FROM pm.unit AS ut WHERE ut.unit_id = :uid)
                ORDER BY u.unit_order
            ',
            [
                'uid' => $result
            ]
        );

        $conversionPotions = [[0, 'Выберите едницу измерения']];

        while($row = $units->fetch()) {
            $conversionPotions[] = [(int)$row['unit_id'], $row['unit_title_short']];
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result,
            'html' => $this->view->getPartial(
                'units/partials/unit_item',
                [
                    'unit_id' => $result,
                    'unit_title_short' => $unitTitle,
                    'unit_status_id' => 1,
                    'conversion_options' => $conversionPotions,
                ]
            )
        ]);
    }

    private function unitUpdate() {
        $unitId = (int)$this->request->get('id');
        $typeId = (int)$this->request->getPost('type');
        $titleShort = trim($this->request->getPost('title_short'));
        $titleFull  = trim($this->request->getPost('title_full'));
        $ofUnitId = (int)$this->request->getPost('of_unit');
        $toUnitId = (int)$this->request->getPost('to_unit');

        $result = (int)$this->db->query(
            '
                SELECT pm.unit_update(
                       :mid
                     , :uid
                     , :tid
                     , :short
                     , :full
                     , :ouid
                     , :tuid
                     ) AS res
            ',
            [
                'uid' => $unitId,
                'mid' => $this->user->getId(),
                'tid' => $typeId,
                'short' => $titleShort,
                'full' => $titleFull,
                'ouid' => $ofUnitId,
                'tuid' => $toUnitId
            ]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Название не может быть пустым',
                    -2 => 'Название занято',
                    -3 => 'Необходимо указать единицы измерения для соотношения',
                    -4 => 'В соотношении не могут быть одинаковые единицы измерения',
                    -5 => 'Единица измерения заблокирована'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'notification' => 'Единица измерения изменена',
            'result' => $result
        ]);
    }

    private function unitDelete() {
        $result = (int)$this->db->query(
            'SELECT pm.unit_delete(:id) AS res',
            ['id' => (int)$this->request->getPost('unit_id')]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Единица измерения связана со свойствами',
                    -2 => 'Единица измерения заблокирована'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);

    }

    private function unitSort() {
        $units = json_decode($this->request->getPost('units'), true);

        if (is_array($units)) {
            $unitsSort = [];

            for($a = 0, $len = sizeof($units); $a < $len; $a++) {
                $unitsSort[] = (int)$units[$a];
            }

            $this->db->query(
                'SELECT pm.unit_sort(:order::jsonb)',
                ['order' => json_encode($unitsSort)]
            );
        }

        $this->sendResponseAjax([
            'state'  => 'yes'
        ]);
    }

    private function unitLock() {
        $this->db->query(
            'SELECT pm.unit_update_lock(:mid, :uid, :lock)',
                [
                    'mid' => $this->user->getId(),
                    'uid' => (int)$this->request->getPost('unit_id'),
                    'lock' => (int)$this->request->getPost('lock_state') ? 1 : 0
                ]
            );

        $this->sendResponseAjax([
            'state'  => 'yes'
        ]);
    }

    private function conversionCreate() {
        $unitId = (int)$this->request->get('unit_id');
        $containId = trim($this->request->getPost('contain_unit_id'));
        $conversionUnits = trim($this->request->getPost('conversion_units'));

        $result = (int)$this->db->query(
            'SELECT pm.unit_conversion_create(:mid, :uid, :cid, :units) AS res',
            [
                'mid' => $this->user->getId(),
                'uid' => $unitId,
                'cid' => $containId,
                'units' => $conversionUnits
            ]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Единицы измерения должны быть разными',
                    -2 => 'Такое соотношение уже существует',
                    -3 => 'Количество должно быть больше нуля',
                ][$result]
            ]);
        }

        $unitRow = $this->db->query(
            'SELECT unit_title_short, unit_group_id FROM pm.unit WHERE unit_id = :uid',
            ['uid' => $unitId]
        )->fetch();

        $units = $this->db->query(
            '
                SELECT unit_id, unit_title_short
                FROM pm.unit 
                WHERE unit_group_id = :gid
                ORDER BY unit_order
            ',
            [
                'gid' => $unitRow['unit_group_id']
            ]
        );

        $unitOptions = [[0, 'Выберите едницу измерения']];

        while($row = $units->fetch()) {
            $unitOptions[] = [(int)$row['unit_id'], $row['unit_title_short']];
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result,
            'html' => $this->view->getPartial(
                'units/partials/unit_conversion',
                [
                    'unit_id' => $unitId,
                    'contain_unit_id' => $containId,
                    'title_short' => $unitRow['unit_title_short'],
                    'conversion_units' => $conversionUnits,
                    'conversion_options' => $unitOptions
                ]
            )
        ]);
    }

    private function conversionUpdate() {
        $unitId = (int)$this->request->get('unit_id');
        $containId = (int)$this->request->getPost('contain_unit_id');
        $conversionUnits = trim($this->request->getPost('conversion_units'));

        $result = (int)$this->db->query(
            'SELECT pm.unit_conversion_update(:mid, :uid, :cid, :units) AS res',
            [
                'mid' => $this->user->getId(),
                'uid' => $unitId,
                'cid' => $containId,
                'units' => $conversionUnits
            ]
        )->fetch()['res'];

        $this->sendResponseAjax([
            'state' => 'yes',
            'notification' => 'Соотношение единиц измерения изменено',
            'result' => $result
        ]);
    }

    private function conversionDelete() {
        $result = (int)$this->db->query(
            'SELECT pm.unit_conversion_delete(:uid, :cid) AS res',
            [
                'uid' => (int)$this->request->getPost('unit_id'),
                'cid' => (int)$this->request->getPost('contain_unit_id')
            ]
        )->fetch()['res'];

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);
    }
}