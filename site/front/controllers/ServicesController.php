<?php
namespace Site\Front\Controllers;


use Core\Extender\ControllerApp;

/**
 * Class ServicesController
 * @package Site\Front\Controllers
 */
class ServicesController extends ControllerApp
{
    public function initialize()
    {
        parent::initialize();

        $this->hapi->setHapiController('services');
    }

    public function indexAction()
    {
        $this->hapi->setHapiAction('index');

        $this->view->pick('services/list');
    }

    public function serviceAction($serviceId = 0)
    {
        $serviceId = (int)$serviceId;

        if ($this->request->isPost()) {
            $act = $this->request->get('act');

            if ($act == 'component_create') {
                $this->componentCreate($serviceId);
            }
            else if ($act == 'component_delete') {
                $this->componentDelete();
            }
            else if ($act == 'component_update') {
                $this->componentUpdate();
            }
            else if ($act == 'component_toggle_field') {
                $this->componentToggleField();
            }
            else if ($act == 'property_delete') {
                $this->propertyDelete();
            }
            else if ($act == 'property_create') {
                $this->propertyCreate();
            }
            else if ($act == 'property_update') {
                $this->propertyUpdate();
            }
            else if ($act == 'property_sort') {
                $this->propertySort();
            }
        }

        $this->view->setVar('mainMenuData', [
            'active' => 'pm_dict_services',
            'overlay' => 1,
            'template' => 'partials/menu/menu_dicts',
            'icon' => 'service.svg',
            'icon_size'  => 'width:17px;',
            'title' => 'Базовые услуги',
        ]);

        $this->hapi->setHapiAction('service');

        $serviceRow = $this->db->query(
            '
                SELECT s.service_title
                     , sss.service_status_id
                FROM pm.service AS s
                   , pm.service_status_state AS sss
                WHERE s.service_id = :sid
                  AND sss.service_status_state_id = s.service_status_state_id
            ',
            [
                'sid' => $serviceId
            ]
        )->fetch();

        $this->view->setVar('serviceRow', $serviceRow);

        $components = $this->db->query(
            '                
                SELECT DISTINCT
                       sc.service_component_id
                     , COALESCE(sc.parent_service_component_id, 0) AS parent_service_component_id
                     , sc.component_title
                     , sc.component_mandatory
                     , sc.component_multiple
                     , sss.service_status_id
                FROM pm.service_component AS scs
                   , pm.service_component_tree AS sct
                   , pm.service_component AS sc
                   , pm.service_status_state sss
                WHERE scs.service_id = :sid
                  AND sct.parent_service_component_id = scs.service_component_id  
                  AND sc.service_component_id = sct.service_component_id
                  AND sss.service_status_state_id = sc.service_status_state_id
                  AND COALESCE(sc.parent_service_component_id, 0) <> sct.parent_service_component_id
                ORDER BY sc.component_title
            
            ',
            [
                'sid' => $serviceId
            ]
        );

        $componentsMap = [];
        $componentsTree = [];

        while($row = $components->fetch()) {
            $parentId = (int)$row['parent_service_component_id'];
            $componentId = (int)$row['service_component_id'];

            $componentsTree[$parentId] = $componentsTree[$parentId] ?? [];
            $componentsTree[$parentId][] = $componentId;

            $row['components'] = [];
            $row['properties'] = [];
            $row['properties_active'] = 0;

            $componentsMap[$componentId] = $row;
        }

        $componentsList = [];

        foreach($componentsTree as $nodeComponentId => $childComponentsList) {
            if ($nodeComponentId == 0) {
                foreach ($childComponentsList as $childComponentId) {
                    $componentsList[] = &$componentsMap[$childComponentId];
                }
            }
            else {
                foreach ($childComponentsList as $childComponentId) {
                    $componentsMap[$nodeComponentId]['components'][] = &$componentsMap[$childComponentId];
                }
            }
        }

        $properties = $this->db->query(
            '
                SELECT scp.service_component_id
                     , scp.service_component_property_id
                     , scp.property_title
                     , spt.type_title
                     , spt.type_icon
                     , scp.property_mandatory
                     , scp.property_multiple
                     , scp.service_property_type_id
                     , sss.service_status_id
                     , COALESCE(scpd.service_dict_id, 0) AS service_dict_id
                     , COALESCE(sd.dict_title, \'\') AS dict_title
                     , COALESCE(scpu.unit_id, 0) AS unit_id
                     , COALESCE(u.unit_title_short, \'Любая ЕЗ\') AS unit_title
                     , COALESCE(scpu.unit_group_id, 0) AS unit_group_id
                     , COALESCE(ug.group_title, \'\') AS group_title
                FROM pm.service_component AS sc
                   , pm.service_component_property AS scp
                     LEFT JOIN pm.service_component_property_dict AS scpd 
                          ON   scpd.service_component_property_id = scp.service_component_property_id
                     LEFT JOIN pm.service_dict AS sd ON sd.service_dict_id = scpd.service_dict_id
                     LEFT JOIN pm.service_component_property_unit AS scpu
                          ON   scpu.service_component_property_id = scp.service_component_property_id
                     LEFT JOIN pm.unit_group AS ug ON ug.unit_group_id = scpu.unit_group_id
                     LEFT JOIN pm.unit AS u ON u.unit_id = scpu.unit_id
                   , pm.service_property_type AS spt
                   , pm.service_status_state AS sss
                WHERE sc.service_id = :sid
                  AND scp.service_component_id = sc.service_component_id
                  AND spt.service_property_type_id = scp.service_property_type_id
                  AND sss.service_status_state_id = scp.service_status_state_id
                ORDER BY scp.property_order ASC 
            ',
            [
                'sid' => $serviceId
            ]
        );

        while($row = $properties->fetch()) {
            $componentsMap[$row['service_component_id']]['properties_active'] += $row['service_status_id'] == 2 ? 1 : 0;
            $componentsMap[$row['service_component_id']]['properties'][] = $row;
        }

        $this->view->setVar('componentsList', $componentsList);

        $this->prepareDictionaries();
    }

    public function listAction()
    {
        if ($this->request->isPost()) {
            $act = $this->request->get('act');

            if ($act == 'category_create') {
                $this->categoryCreate();
            }
            else if ($act == 'category_update') {
                $this->categoryUpdate();
            }
            else if ($act == 'category_delete') {
                $this->categoryDelete();
            }
            else if ($act == 'service_create') {
                $this->serviceCreate();
            }
            else if ($act == 'service_update') {
                $this->serviceUpdate();
            }
            else if ($act == 'service_delete') {
                $this->serviceDelete();
            }
        }

        $this->view->setVar('mainMenuData', [
            'active' => 'pm_dict_services',
            'overlay' => 1,
            'template' => 'partials/menu/menu_dicts',
            'icon' => 'service.svg',
            'icon_size'  => 'width:17px;',
            'title' => 'Базовые услуги',
        ]);

        $this->hapi->setHapiAction('list');

        $categories = $this->db->query(
            '
                SELECT service_category_id
                     , category_title
                     , category_create_date
                     , category_update_date
                     , create_member_id
                     , create_member_nick
                     , update_member_id
                     , update_member_nick
                FROM pm.service_category_vw 
            '
        );

        $categoriesMap = [];

        while($row = $categories->fetch()) {
            $row['services'] = [];

            $categoriesMap[$row['service_category_id']] = $row;
        }

        $services = $this->db->query(
            '
                SELECT service_id
                     , service_title
                     , service_create_date
                     , service_update_date
                     , service_status_id
                     , status_title
                     , service_category_id
                     , create_member_id
                     , create_member_nick
                     , update_member_id
                     , update_member_nick
                     , component_total
                     , count_map
                FROM pm.service_vw
            '
        );

        while($row = $services->fetch()) {
            $row['count_map'] = json_decode($row['count_map'], true);

            $categoriesMap[$row['service_category_id']]['services'][] = $row;
        }
/*
        echo '<pre>';
        print_r($categoriesMap);

        exit;*/
        $this->view->setVar('servicesCategoriesMap', $categoriesMap);
    }

    public function dictsAction()
    {
        $this->view->setVar('mainMenuData', [
            'active' => 'pm_dict_services_dicts',
            'overlay' => 1,
            'template' => 'partials/menu/menu_dicts',
            'icon' => 'books.svg',
            'icon_size'  => 'width:18px;',
            'title' => 'Свойства компонентов',
        ]);

        $this->hapi->setHapiAction('dicts');

        if ($this->request->isPost()) {
            $act = $this->request->get('act');

            if ($act == 'group_create') {
                $this->dictGroupCreate();
            }
            else if ($act == 'group_update') {
                $this->dictGroupUpdate();
            }
            else if ($act == 'group_delete') {
                $this->dictGroupDelete();
            }
            else if ($act == 'dict_create') {
                $this->dictCreate();
            }
            else if ($act == 'dict_update') {
                $this->dictUpdate();
            }
            else if ($act == 'dict_delete') {
                $this->dictDelete();
            }
            else if ($act == 'item_create') {
                $this->itemCreate();
            }
            else if ($act == 'item_update') {
                $this->itemUpdate();
            }
            else if ($act == 'item_delete') {
                $this->itemDelete();
            }
        }

        $groups = $this->db->query(
            '
                SELECT sdg.service_dict_group_id
                     , sdg.group_title
                FROM pm.service_dict_group AS sdg 
                ORDER BY sdg.group_title
            '
        );

        $groupsMap = [];

        while($row = $groups->fetch()) {
            $row['dicts'] = [];

            $groupsMap[$row['service_dict_group_id']] = $row;
        }
        
        $dicts = $this->db->query(
            '
                SELECT sdv.service_dict_group_id
                     , sdv.service_dict_id
                     , sdv.service_status_id
                     , sdv.dict_title
                     , sdv.group_title
                     , sdv.items_count
                     , sdv.active_count
                FROM pm.service_dict_vw AS sdv
                ORDER BY sdv.dict_title
            '
        );

        $dictsMap = [];

        while($row = $dicts->fetch()) {
            $row['items'] = [];

            $dictsMap[$row['service_dict_id']] = $row;

            $groupsMap[$row['service_dict_group_id']]['dicts'][] = &$dictsMap[$row['service_dict_id']];
        }

        $items = $this->db->query(
            '
                SELECT sdi.service_dict_id
                     , sdi.service_dict_item_id
                     , sdi.item_title
                     , sdi.service_status_id
                     , sdi.status_title
                     , -1 AS linked_count
                FROM pm.service_dict_item_vw AS sdi
                ORDER BY sdi.item_title
            '
        );

        while($row = $items->fetch()) {
            $dictsMap[$row['service_dict_id']]['items'][] = $row;
        }

        $this->view->setVar('dictsGroupsMap', $groupsMap);
    }

    private function prepareDictionaries() {
        $types = $this->db->query(
            '
                SELECT spt.service_property_type_id
                     , spt.type_title
                     , spt.type_primitive
                     , spt.type_unit
                     , spt.type_dict
                FROM pm.service_property_type AS spt
                ORDER BY type_title
            '
        );

        $propertyTypesMap = [];
        $propertyTypesOptions = [[0, 'Выберите тип']];

        while($row = $types->fetch()) {
            $propertyTypesMap[$row['service_property_type_id']] = $row;

            $propertyTypesOptions[] = [
                (int)$row['service_property_type_id'],
                $row['type_title']
            ];
        }

        $this->view->setVar('propertyTypesMap', $propertyTypesMap);
        $this->view->setVar('propertyTypesOptions', $propertyTypesOptions);

        $groups = $this->db->query(
            '
                SELECT ug.unit_group_id
                     , ug.group_title
                FROM pm.unit_group AS ug
                ORDER BY ug.group_title
            '
        );

        $groupsOptions = [[0, 'Группа ЕЗ']];

        while($row = $groups->fetch()) {
            $groupsOptions[] = [(int)$row['unit_group_id'], $row['group_title']];
        }

        $this->view->setVar('unitsGroupsOptions', $groupsOptions);

        $units = $this->db->query(
            '
                SELECT u.unit_id
                     , u.unit_group_id
                     , u.unit_title_short
                FROM pm.unit AS u
                ORDER BY u.unit_order
            '
        );

        $unitsOptionsMap = [];

        while($row = $units->fetch()) {
            if (!isset($unitsOptionsMap[$row['unit_group_id']])) {
                $unitsOptionsMap[$row['unit_group_id']] = [[0, 'Любая ЕЗ']];
            }

            $unitsOptionsMap[$row['unit_group_id']][] = [(int)$row['unit_id'], $row['unit_title_short']];
        }

        $this->view->setVar('unitsOptionsMap', $unitsOptionsMap);


        $dicts = $this->db->query(
            '
                SELECT sdv.service_dict_id
                     , sdv.dict_title
                     , sdv.group_title
                FROM pm.service_dict_vw AS sdv
                WHERE service_status_id = 2
                ORDER BY sdv.group_title, sdv.dict_title
            '
        );

        $dictsOptions = [[0, 'Выберите справочник']];

        $dictsGroups = [];

        while($row = $dicts->fetch()) {
            if (!isset($dictsGroups[$row['group_title']])) {
                $dictsGroups[$row['group_title']] = [
                    'label' => $row['group_title'],
                    'list' => []
                ];

                $dictsOptions[] = &$dictsGroups[$row['group_title']];
            }

            $dictsGroups[$row['group_title']]['list'][] = [$row['service_dict_id'], $row['dict_title']];
        }

        $this->view->setVar('dictsOptions', $dictsOptions);

        return [
            'types' => $propertyTypesMap,
            'units' => $unitsOptionsMap,
        ];
    }

    private function categoryCreate() {
        $categoryTitle = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.service_category_create(:mid, :title) AS res',
            [
                'mid' => $this->user->getId(),
                'title' => $categoryTitle
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
                'services/partials/service_category',
                [
                    'service_category_id' => $result,
                    'category_title' => $categoryTitle,
                    'services' => []
                ]
            )
        ]);
    }

    private function categoryUpdate() {
        $categoryId = (int)$this->request->get('id');
        $categoryTitle = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.service_category_update(:cid, :mid, :title) AS res',
            [
                'cid' => $categoryId,
                'mid' => $this->user->getId(),
                'title' => $categoryTitle
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

    private function categoryDelete() {
        $result = (int)$this->db->query(
            'SELECT pm.service_category_delete(:id) AS res',
            ['id' => (int)$this->request->getPost('category_id')]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Категория связана с услугами'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);

    }

    private function serviceCreate() {
        $categoryId = (int)$this->request->get('category_id');
        $serviceTitle = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.service_create(:mid, :cid, :title) AS res',
            [
                'mid' => $this->user->getId(),
                'cid' => $categoryId,
                'title' => $serviceTitle
            ]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Название не может быть пустым',
                    -2 => 'Есть черновик или активная услуга с таким названием'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result,
            'html' => $this->view->getPartial(
                'services/partials/service_item',
                [
                    'service_id' => $result,
                    'service_title' => $serviceTitle,
                    'service_status_id' => 1
                ]
            )
        ]);
    }

    private function serviceUpdate() {
        $serviceId = (int)$this->request->get('id');
        $serviceTitle = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.service_update_title(:sid, :mid, :title) AS res',
            [
                'sid' => $serviceId,
                'mid' => $this->user->getId(),
                'title' => $serviceTitle
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

    private function serviceDelete() {
        $result = (int)$this->db->query(
            'SELECT pm.service_delete(:id) AS res',
            ['id' => (int)$this->request->getPost('service_id')]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'У услуги есть компоненты'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);
    }



    private function componentDelete() {
        $result = (int)$this->db->query(
            'SELECT pm.service_component_delete(:id) AS res',
            ['id' => (int)$this->request->getPost('component_id')]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Существуют экземпляры компонента',
                    -2 => 'Существуют свойста у компонента',
                    -3 => 'Существуют дочерние компоненты',
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);
    }

    private function componentCreate($serviceId) {
        $componentTitle = trim($this->request->getPost('title'));
        $parentComponentId = (int)$this->request->getPost('parent');

        $result = (int)$this->db->query(
            'SELECT pm.service_component_create(:mid, :sid, :title, :pid) AS res',
            [
                'sid' => $serviceId,
                'mid' => $this->user->getId(),
                'pid' => $parentComponentId,
                'title' => $componentTitle
            ]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Название не может быть пустым',
                    -2 => 'Название занято',
                    -3 => 'Родительский компонент не существует',
                    -4 => 'Услуга находится в архиве',
                    -5 => 'Родительский компонент находится в архиве'
                ][$result]
            ]);
        }


        $this->prepareDictionaries();

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result,
            'html' => $this->view->getPartial(
                'services/partials/service_component',
                [
                    'service_component_id' => $result,
                    'component_title' => $componentTitle,
                    'properties' => []
                ]
            )
        ]);
    }

    private function componentUpdate() {
        $componentId = (int)$this->request->get('id');
        $componentTitle = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.service_component_update(:cid, :mid, :title) AS res',
            [
                'cid' => $componentId,
                'mid' => $this->user->getId(),
                'title' => $componentTitle
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

    private function componentToggleField() {
        $componentId = (int)$this->request->get('service_component_id');
        $fieldName = trim($this->request->getPost('field')) == 'mandatory' ? 'mandatory' : 'multiple';
        $isActive = (int)$this->request->getPost('is_active');

        $result = (int)$this->db->query(
            'SELECT pm.service_component_toggle_'.$fieldName.'(:mid, :cid, :state) AS res',
            [
                'cid' => $componentId,
                'mid' => $this->user->getId(),
                'state' => $isActive
            ]
        )->fetch()['res'];

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);
    }

    private function propertyDelete() {
        $result = (int)$this->db->query(
            'SELECT pm.service_component_property_delete(:id) AS res',
            ['id' => (int)$this->request->getPost('property_id')]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Существуют экземпляры свойства'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);
    }

    private function propertyCreate() {
        $componentId = (int)$this->request->get('component_id');
        $propertyTitle = trim($this->request->getPost('title'));
        $propertyTypeId = (int)$this->request->getPost('type');
        $propertyDictId = (int)$this->request->getPost('dict');
        $propertyUnitId = (int)$this->request->getPost('unit_value');
        $propertyUnitGroupId = (int)$this->request->getPost('unit_group');
        $propertyMultiple = $this->request->getPost('multiple') == 'on' ? 1 : 0;
        $propertyMandatory = $this->request->getPost('mandatory') == 'on' ? 1 : 0;

        $result = (int)$this->db->query(
            '
                SELECT pm.service_component_property_create(:mid, :cid, :tit, :tid, :man, :mul, :did, :gid, :uid) AS res
            ',
            [
                'mid' => $this->user->getId(),
                'cid' => $componentId,
                'tit' => $propertyTitle,
                'tid' => $propertyTypeId,
                'man' => $propertyMandatory,
                'mul' => $propertyMultiple,
                'did' => $propertyDictId,
                'gid' => $propertyUnitGroupId,
                'uid' => $propertyUnitId
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

        $this->prepareDictionaries();

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result,
            'html' => $this->view->getPartial(
                'services/partials/service_component_property',
                [
                    'service_component_id' => $componentId,
                    'service_component_property_id' => $result,
                    'property_mandatory' => $propertyMandatory,
                    'property_multiple' => $propertyMultiple,
                    'service_status_id' => 1,
                    'service_property_type_id' => $propertyTypeId,
                    'property_title' => $propertyTitle,
                    'service_dict_id' => $propertyDictId,
                    'unit_id' => $propertyUnitId,
                    'unit_group_id' => $propertyUnitGroupId,
                ]
            )
        ]);
    }

    private function propertyUpdate() {
        $propertyId = (int)$this->request->getPost('property_id');
        $propertyTitle = trim($this->request->getPost('title'));
        $propertyTypeId = (int)$this->request->getPost('type');
        $propertyDictId = (int)$this->request->getPost('dict');
        $propertyUnitId = (int)$this->request->getPost('unit_value');
        $propertyUnitGroupId = (int)$this->request->getPost('unit_group');
        $propertyMultiple = $this->request->getPost('multiple') == 'on' ? 1 : 0;
        $propertyMandatory = $this->request->getPost('mandatory') == 'on' ? 1 : 0;

        $result = (int)$this->db->query(
            '
                SELECT pm.service_component_property_update(:mid, :pid, :tit, :tid, :man, :mul, :did, :gid, :uid) AS res
            ',
            [
                'mid' => $this->user->getId(),
                'pid' => $propertyId,
                'tit' => $propertyTitle,
                'tid' => $propertyTypeId,
                'man' => $propertyMandatory,
                'mul' => $propertyMultiple,
                'did' => $propertyDictId,
                'gid' => $propertyUnitGroupId,
                'uid' => $propertyUnitId
            ]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Название не может быть пустым',
                    -2 => 'Название занято',
                    -3 => 'Свойство может меняться только в состоянии черновика'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result,
            'notification' => 'Свойство изменено'
        ]);
    }

    private function propertySort() {
        $properties = json_decode($this->request->getPost('properties'), true);

        if (is_array($properties)) {
            $propertiesSort = [];

            for($a = 0, $len = sizeof($properties); $a < $len; $a++) {
                $propertiesSort[] = (int)$properties[$a];
            }

            $this->db->query(
                'SELECT pm.service_component_property_sort(:order::jsonb)',
                ['order' => json_encode($propertiesSort)]
            );
        }

        $this->sendResponseAjax([
            'state'  => 'yes'
        ]);
    }

    private function dictGroupCreate() {
        $groupTitle = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.service_dict_group_create(:mid, :title) AS res',
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
                'services/partials/dict_group',
                [
                    'service_dict_group_id' => $result,
                    'group_title' => $groupTitle,
                    'dicts' => []
                ]
            )
        ]);
    }

    private function dictGroupUpdate() {
        $groupId = (int)$this->request->get('id');
        $groupTitle = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.service_dict_group_update(:cid, :mid, :title) AS res',
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

    private function dictGroupDelete() {
        $result = (int)$this->db->query(
            'SELECT pm.service_dict_group_delete(:id) AS res',
            ['id' => (int)$this->request->getPost('group_id')]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'В группе есть справочники'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);
    }

    private function dictCreate() {
        $groupId = (int)$this->request->get('group_id');
        $dictTitle = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.service_dict_create(:mid, :gid, :title) AS res',
            [
                'mid' => $this->user->getId(),
                'gid' => $groupId,
                'title' => $dictTitle
            ]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Название не может быть пустым',
                    -2 => 'Есть черновик или активный справочник с таким названием'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result,
            'html' => $this->view->getPartial(
                'services/partials/dict_dict',
                [
                    'service_dict_id' => $result,
                    'dict_title' => $dictTitle,
                    'service_status_id' => 1
                ]
            )
        ]);
    }

    private function dictUpdate() {
        $dictId = (int)$this->request->get('id');
        $dictTitle = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.service_dict_update_title(:did, :mid, :title) AS res',
            [
                'did' => $dictId,
                'mid' => $this->user->getId(),
                'title' => $dictTitle
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

    private function dictDelete() {
        $result = (int)$this->db->query(
            'SELECT pm.service_dict_delete(:id) AS res',
            ['id' => (int)$this->request->getPost('dict_id')]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Справочник связан со свойствами',
                    -2 => 'Справочник заблокирован'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);

    }

    private function itemCreate() {
        $dictId = (int)$this->request->get('dict_id');
        $itemTitle = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.service_dict_item_create(:mid, :did, :title) AS res',
            [
                'mid' => $this->user->getId(),
                'did' => $dictId,
                'title' => $itemTitle
            ]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Название не может быть пустым',
                    -2 => 'Есть черновик или активная запись с таким названием'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result,
            'html' => $this->view->getPartial(
                'services/partials/dict_item',
                [
                    'service_dict_item_id' => $result,
                    'service_dict_id' => $dictId,
                    'item_title' => $itemTitle,
                    'service_status_id' => 1,
                    'linked_count' => -1
                ]
            )
        ]);
    }

    private function itemUpdate() {
        $itemId = (int)$this->request->get('item_id');
        $itemTitle = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.service_dict_item_update_title(:iid, :mid, :title) AS res',
            [
                'iid' => $itemId,
                'mid' => $this->user->getId(),
                'title' => $itemTitle
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

    private function itemDelete() {
        $result = (int)$this->db->query(
            'SELECT pm.service_dict_item_delete(:id) AS res',
            ['id' => (int)$this->request->getPost('item_id')]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Запись справочника связана с экземплярами свойств',
                    -2 => 'Запись заблокирована'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);

    }

    protected function apiServiceChangeStatus() {
        $statusId = (int)$this->request->getPost('service_status_id');
        $serviceId = (int)$this->request->getPost('service_id');

        $result = (int)$this->db->query(
            'SELECT pm.service_update_status(:rid, :mid, :sid) AS res',
            [
                'rid' => $serviceId,
                'mid' => $this->user->getId(),
                'sid' => $statusId
            ]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Нельзя архивировать услугу с активными компонентами',
                    -2 => 'Нельзя активировать услугу без активных компонентов'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);
    }

    protected function apiDictChangeStatus() {
        $statusId = (int)$this->request->getPost('service_status_id');
        $dictId = (int)$this->request->getPost('dict_id');

        $result = (int)$this->db->query(
            'SELECT pm.service_dict_update_status(:did, :mid, :sid) AS res',
            [
                'did' => $dictId,
                'mid' => $this->user->getId(),
                'sid' => $statusId
            ]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Нельзя архивировать справочник с активными позициями',
                    -2 => 'Нельзя активировать справочник без активных позиций'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);
    }

    protected function apiItemChangeStatus() {
        $statusId = (int)$this->request->getPost('service_status_id');
        $itemId = (int)$this->request->getPost('item_id');

        $result = (int)$this->db->query(
            'SELECT pm.service_dict_item_update_status(:did, :mid, :sid) AS res',
            [
                'did' => $itemId,
                'mid' => $this->user->getId(),
                'sid' => $statusId
            ]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);
    }

    protected function apiComponentChangeStatus() {
        $statusId = (int)$this->request->getPost('service_status_id');
        $componentId = (int)$this->request->getPost('component_id');

        $result = (int)$this->db->query(
            'SELECT pm.service_component_update_status(:cid, :mid, :sid) AS res',
            [
                'cid' => $componentId,
                'mid' => $this->user->getId(),
                'sid' => $statusId
            ]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);
    }

    protected function apiPropertyChangeStatus() {
        $statusId = (int)$this->request->getPost('service_status_id');
        $propertyId = (int)$this->request->getPost('property_id');

        $result = (int)$this->db->query(
            'SELECT pm.service_component_property_update_status(:pid, :mid, :sid) AS res',
            [
                'pid' => $propertyId,
                'mid' => $this->user->getId(),
                'sid' => $statusId
            ]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => ''
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result,
            'notification' => 'Статус свойства изменен',
            'html' => $this->view->getPartial(
                'services/partials/service_component_property',
                $this->db->query(
                    '
                        SELECT scp.service_component_id
                             , scp.service_component_property_id
                             , scp.property_title
                             , spt.type_title
                             , spt.type_icon
                             , scp.property_mandatory
                             , scp.property_multiple
                             , scp.service_property_type_id
                             , sss.service_status_id
                             , COALESCE(scpd.service_dict_id, 0) AS service_dict_id
                             , COALESCE(sd.dict_title, \'\') AS dict_title
                             , COALESCE(scpu.unit_id, 0) AS unit_id
                             , COALESCE(u.unit_title_short, \'Любая ЕЗ\') AS unit_title
                             , COALESCE(scpu.unit_group_id, 0) AS unit_group_id
                             , COALESCE(ug.group_title, \'\') AS group_title
                        FROM pm.service_component_property AS scp
                             LEFT JOIN pm.service_component_property_dict AS scpd 
                                  ON   scpd.service_component_property_id = scp.service_component_property_id
                             LEFT JOIN pm.service_dict AS sd ON sd.service_dict_id = scpd.service_dict_id
                             LEFT JOIN pm.service_component_property_unit AS scpu
                                  ON   scpu.service_component_property_id = scp.service_component_property_id
                             LEFT JOIN pm.unit_group AS ug ON ug.unit_group_id = scpu.unit_group_id
                             LEFT JOIN pm.unit AS u ON u.unit_id = scpu.unit_id
                           , pm.service_property_type AS spt
                           , pm.service_status_state AS sss
                        WHERE scp.service_component_property_id = :pid
                          AND spt.service_property_type_id = scp.service_property_type_id
                          AND sss.service_status_state_id = scp.service_status_state_id
                        ORDER BY scp.property_title ASC 
                    ',
                    [
                        'pid' => $propertyId
                    ]
                )->fetch()
            )
        ]);
    }

    protected function apiDictionaries() {
        $this->sendResponseAjax(array_merge(
            ['state' => 'yes'],
            $this->prepareDictionaries()
        ));
    }
}