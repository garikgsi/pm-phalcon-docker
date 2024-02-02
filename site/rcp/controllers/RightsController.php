<?php
namespace Site\Rcp\Controllers;

use Core\Extender\ControllerAppRcp;
use Core\Lib\AjaxFormResponse;
use Phalcon\Filter;
use Phalcon\Mvc\View;


class RightsController extends ControllerAppRcp
{
    public function initialize()
    {
        parent::initialize();

        $this->view->setLayout('content_wrap');

        $this->breadcrumbs->addCrumb('/rights', $this->t->_('right_title'), View::LEVEL_LAYOUT);

        $this->hapi->setHapiController('rights');
        $this->view->setVar('rcpMainMenuActiveItem', 'rcp_menu_rights');
        $this->attachHapiCallback(3, 'defaultMainMenuClick', '', []);
    }


    public function indexAction()
    {
        $this->buttonsForAdd();

        $this->view->setVar('rightsList', $this->getGroupedRightsList());


        $this->site->setMetaTitle($this->t->_('rights_title'));
        $this->view->setVar('content_wrap_title', $this->t->_('rights_title'));
        $this->hapi->setHapiAction('index');
    }

    public function addAction($rightId = 0)
    {
        $this->breadcrumbs->addCrumb('/rights/add', $this->t->_('right_add_title'), View::LEVEL_LAYOUT);

        $this->site->setMetaTitle($this->t->_('right_add_title'));
        $this->view->setVar('content_wrap_title', $this->t->_('right_add_title'));

        $this->view->setVar('rights_select_list', $this->prepareRightsSelect());
        $this->view->setvar('right_parent_id', $rightId);

        $this->hapi->setHapiAction('add');
    }

    public function editAction($rightId)
    {
        $rightId = (int)$rightId;

        $rightRow = $this->db->query(
            'SELECT * FROM rights_list_complete WHERE right_id = :rid::integer AND lang_id = 1',
            ['rid' => $rightId]
        )->fetch();

        $groupsLinks = $this->db->query(
            'SELECT * FROM rights_granted_to_groups WHERE lang_id = :lid AND right_id = :rid',
            [
                'lid' => $this->user->getLangId(),
                'rid' => $rightId
            ]
        )->fetchAll();

        $membersLinks = $this->db->query(
            'SELECT * FROM rights_granted_to_members WHERE lang_id = :lid AND right_id = :rid',
            [
                'lid' => $this->user->getLangId(),
                'rid' => $rightId
            ]
        )->fetchAll();


        $this->view->setVar('rightGroupsList', $groupsLinks);
        $this->view->setVar('rightMembersList', $membersLinks);

        $this->view->setVar('rights_select_list', $this->prepareRightsSelect($rightId));
        $this->view->setVar('rightRow', $rightRow);

        $this->buttonsForAdd();

        $this->breadcrumbs->addCrumb('/rights/edit/'.$rightId, $this->t->_('right_edit_title'), View::LEVEL_LAYOUT);
        $this->site->setMetaTitle($this->t->_('right_edit_title'));
        $this->view->setVar('content_wrap_title', $this->t->_('right_edit_title'));

        $this->hapi->setHapiAction('edit');
    }

    public function getRightsForApp($appId)
    {
        return $this->prepareRightsList($this->db->query(
            '
                SELECT 
                    parent_id,   
                    right_id,
                    right_in_use,
                    right_name,
                    right_title,
                    right_description
                FROM rights_to_apps_list WHERE app_id = :aid AND lang_id = :lid
            ',
            ['aid' => $appId, 'lid' => 1]
        ));
    }

    public function getRightsForSite($siteId)
    {
        return $this->prepareRightsList($this->db->query(
            '
                SELECT 
                    parent_id,   
                    right_id,
                    right_in_use,
                    right_name,
                    right_title,
                    right_description
                FROM rights_to_sites_list WHERE site_id = :sid AND lang_id = :lid',
            ['sid' => $siteId, 'lid' => 1]
        ));
    }

    public function getRightsForController($controllerId)
    {
        return $this->prepareRightsList($this->db->query(
            '
                SELECT 
                    parent_id,   
                    right_id,
                    right_in_use,
                    right_name,
                    right_title,
                    right_description
                FROM rights_to_controllers_list WHERE controller_id = :cid AND lang_id = :lid',
            ['cid' => $controllerId, 'lid' => 1]
        ));
    }

    public function getRightsForGroup($groupId)
    {
        return $this->getGroupedRightsList($groupId, 0, 1);
    }



    private function prepareRightsSelect($rightToDisable = false)
    {
        $rights = $this->db->query(
            'SELECT * FROM rights_list_complete WHERE lang_id = :lid AND object_type = :type',
            ['lid' => $this->user->getLangId(), 'type' => 'none']
        );

        $rightsList = [];

        while($row = $rights->fetch()) {
            $parentId = (int)$row['parent_id'];

            $row['parent_id'] = $parentId;

            if (! isset($rightsList[$parentId])) {
                $rightsList[$parentId] = [];
            }

            $rightsList[$parentId][] = $row;
        }

        $selectList = [[0, 'Нет родителя'], ['label' => 'Список общих прав', 'list' => []]];

        $prepareSelectList = function($nodeId, $level = 0) use($rightsList, &$selectList, &$prepareSelectList, $rightToDisable) {
            if (!isset($rightsList[$nodeId])) {
                return;
            }

            $prefix = str_pad('', $level * 2, '-');

            for($a = 0, $len = sizeof($rightsList[$nodeId]); $a < $len; $a++) {
                $group = $rightsList[$nodeId][$a];

                $localGroupId = (int)$group['right_id'];

                echo $rightToDisable . ' '.$localGroupId;

                $selectList[1]['list'][] = [$localGroupId, $prefix . $group['right_title'], $rightToDisable == $localGroupId];

                if ($rightToDisable != $localGroupId) {
                    $prepareSelectList($localGroupId, $level + 1);
                }
            }
        };

        $prepareSelectList(0, 0);


        return $selectList;
    }

    private function buttonsForAdd() {
        $buttons = [];

        $buttons[] = [
            'text'    => $this->t->_('right_add'),
            'href'    => '/rights/add',
            'variant' => 'send',
            'title'   => $this->t->_('right_add_d'),
            'icon'    => '',
            'id'      => '',
            'classes' => 'elizaHApi',
            'data'    => ['data-level' => 3]
        ];

        $this->view->setVar('content_wrap_title_buttons', $buttons);
    }

    private function getGroupedRightsList($objectId = 0, $isMember = 0, $isGroup = 0)
    {
        $rightsIndexMode = 0;

        if ($isGroup) {
            $dbView = 'SELECT * FROM dp_rights_get_links_to_groups(:oid::integer, :lid::integer)';
        }
        else if ($isMember) {
            $dbView = 'SELECT * FROM dp_rights_get_links_to_members(:oid::bigint, :lid::integer)';
        }
        else {
            $dbView = 'SELECT * FROM rights_list_links_full WHERE lang_id = :lid AND lang_id <> :oid';

            $rightsIndexMode = 1;
        }

        $rightsList = [
            0 => [
                ['icon' => 'ic-folder', 's_icon' => 'ic-play',  'parent_id' => '0', 'id' => 'app_0',  'title' => 'Права приложений'],
                ['icon' => 'ic-folder', 's_icon' => 'ic-earth', 'parent_id' => '0', 'id' => 'site_0', 'title' => 'Права для сайтов'],
                ['icon' => 'ic-folder', 's_icon' => 'ic-cog',   'parent_id' => '0', 'id' => 'controller_0', 'title' => 'Права контроллеров'],
                ['icon' => 'ic-folder', 's_icon' => 'ic-lock',  'parent_id' => '0', 'id' => 'none_0', 'title' => 'Общие права']
            ]
        ];

        $rights = $this->db->query($dbView, ['lid' => $this->user->getLangId(), 'oid' => $objectId ]);

        $objectIcons = [
            'app' => 'ic-play',
            'controller' => 'ic-cog',
            'site' => 'ic-earth'
        ];

        $existObjects = [];

        while($row = $rights->fetch()) {
            $parentId = (int)$row['parent_id'];
            $objectName = $row['object_name'];

            $parentStrId = $objectName.'_'.$parentId;

            if ($parentId == 0 && $row['object_type'] != 'none') {
                $objectTypeId = $row['object_type'].'_0';

                if (!isset($rightsList[$objectTypeId])) {
                    $rightsList[$objectTypeId] = [];
                }

                if (!isset($existObjects[$row['object_type'].'_'.$row['object_name']])) {
                    $rightsList[$objectTypeId][] = [
                        'icon' => $objectIcons[$row['object_type']],
                        'id' => $objectName.'_0',
                        'name' => $row['object_name'],
                        'parent_id' => '0',
                        'title' => $row['object_title']
                    ];

                    $existObjects[$row['object_type'].'_'.$row['object_name']] = 1;
                }
            }
            else if ($parentId == 0 && $row['object_type'] == 'none') {
                $parentStrId = $row['object_type'].'_0';
            }

            if (!isset($rightsList[$parentStrId])) {
                $rightsList[$parentStrId] = [];
            }

            $rightData = [
                'icon' => 'ic-key',
                'id' => $objectName.'_'.$row['right_id'],
                'name' => $row['right_name'],
                'title' => $row['right_title'],
                'mode' => 'right',
                'parent_id' => $row['parent_id'],
                'right_id' => $row['right_id']
            ];


            if ($rightsIndexMode) {
                $groupsCount = (int)$row['groups_count'];
                $membersCount = (int)$row['members_count'];

                $rightData = array_merge($rightData, [
                        'groups_count' => $groupsCount,
                        'members_count' => $membersCount,
                        'is_locked' => $groupsCount || $membersCount ? 1 : 0
                    ],
                    ($row['object_type'] == 'none' ? ['object' => $row['object_type']] : [])
                );
            }
            else {
                $flags = ['is_active' => (int)$row['is_active'] ? 1 : 0];

                if ($isGroup) {
                    $flags['is_inherit']     = (int)$row['is_inherit'] ? 1 : 0;
                    $flags['is_delegating']  = (int)$row['right_given_to_children'] ? 1 : 0;
                    $flags['is_leader_only'] = (int)$row['right_given_to_leader']   ? 1 : 0;
                }

                $rightData = array_merge($rightData, $flags);
            }

            $rightsList[$parentStrId][] = $rightData;
        }

        return $rightsList;
    }

    private function prepareRightsList(\Phalcon\Db\ResultInterface $dbResult)
    {
        $rightsList = [];

        while($row = $dbResult->fetch()) {
            $parentId = (int)$row['parent_id'];

            $row['parent_id'] = $parentId;

            if (! isset($rightsList[$parentId])) {
                $rightsList[$parentId] = [];
            }

            $row['right_id'] = (int)$row['right_id'];
            $row['right_in_use'] = (int)$row['right_in_use'];

            $rightsList[$parentId][] = $row;
        }

        return $rightsList;
    }

    protected function apiRights_langs()
    {
        $controllerId = (int)$this->request->get('controller_id');
        $appId = (int)$this->request->get('app_id');
        $siteId = (int)$this->request->get('site_id');
        $langId = (int)$this->request->get('lang_id');

        $sqlTableAndCriteria = '';
        $contentId = 0;

        if ($controllerId) {
            $sqlTableAndCriteria = 'rights_to_controllers_list WHERE controller_id';
            $contentId = $controllerId;
        }
        else if ($appId) {
            $sqlTableAndCriteria = 'rights_to_apps_list WHERE app_id';
            $contentId = $appId;
        }
        else if ($siteId) {
            $sqlTableAndCriteria = 'rights_to_sites_list WHERE site_id';
            $contentId = $siteId;
        }

        $this->sendResponseAjax([
            'state'  => 'yes',
            'rights' => $this->db->query(
                '
                  SELECT right_id, COALESCE(right_title, \'\') AS right_title, COALESCE(right_description, \'\') AS right_description FROM '.$sqlTableAndCriteria.' = :cid AND lang_id = :lid
                ',
                ['cid' => $contentId, 'lid' => $langId]
            )->fetchAll()
        ]);
    }

    protected function apiRight_edit()
    {

        $postLangId = (int)$this->request->getPost('lang');

        $rightId = (int)$this->request->get('right_id');
        $langId = $postLangId ? $postLangId : (int)$this->request->get('lang_id');

        $name = $this->request->getPost('name');
        $title = trim($this->request->getPost('title'));
        $description = trim($this->request->getPost('description'));

        $this->db->query(
            'SELECT dp_rights_edit(:rid::integer, :lid::integer, :name::varchar(64), :title::varchar(256), :descr::text)',
            [
                'rid' => $rightId,
                'lid' => $langId,
                'name' => $name,
                'title' => $title,
                'descr' => $description
            ]
        )->fetch();

        $reload = 0;

        if (isset($_POST['parent'])) {
            $parentId = (int)$_POST['parent'];

            $result = $this->db->query(
                'SELECT dp_rights_change_parent(:rid::integer, :pid::integer) AS res',
                [
                    'rid' => $rightId,
                    'pid' => $parentId
                ]
            )->fetch()['res'];

            if ($result == -1) {
                $reload = 1;
            }
        }

        $statusManager = (new AjaxFormResponse())->fillPostWithClear();

        $this->sendResponseAjax([
            'state'  => 'yes',
            'notification' => $this->t->_('right_save_complete'),
            'fields' => $statusManager->getStatuses(),
            'reload' => $reload,
            'html' => ''
        ]);
    }

    protected function apiRight_add()
    {
        $postRightId = (int)$this->request->getPost('parent');
        $postLangId = (int)$this->request->getPost('lang');

        $parentRightId = $postRightId ? $postRightId : (int)$this->request->get('right_id');
        $langId = $postLangId ? $postLangId : (int)$this->request->get('lang_id');

        $name = $this->request->getPost('name');
        $title = trim($this->request->getPost('title'));
        $description = trim($this->request->getPost('description'));

        $controllerId = (int)$this->request->get('controller_id');
        $appId = (int)$this->request->get('app_id');
        $siteId = (int)$this->request->get('site_id');


        $statusManager = (new AjaxFormResponse())->fillPostWithClear();

        $getParam = '';

        if ($controllerId) {
            $rightId = (int)$this->db->query(
                'SELECT dp_rights_add_to_controller(:cid::integer, :name, :pid::integer) AS right_id',
                [
                    'cid' => $controllerId,
                    'pid' => $parentRightId,
                    'name' => $name
                ]
            )->fetch()['right_id'];

            $getParam = 'controller_id='.$controllerId;
        }
        else if ($appId) {
            $rightId = (int)$this->db->query(
                'SELECT dp_rights_add_to_app(:aid::integer, :name, :pid::integer) AS right_id',
                [
                    'aid' => $appId,
                    'pid' => $parentRightId,
                    'name' => $name
                ]
            )->fetch()['right_id'];

            $getParam = 'app_id='.$appId;
        }
        else if ($siteId) {
            $rightId = (int)$this->db->query(
                'SELECT dp_rights_add_to_site(:sid::integer, :name, :pid::integer) AS right_id',
                [
                    'sid' => $siteId,
                    'pid' => $parentRightId,
                    'name' => $name
                ]
            )->fetch()['right_id'];

            $getParam = 'site_id='.$siteId;
        }
        else {
            $rightId = (int)$this->db->query(
                'SELECT  dp_rights_add(:name, :pid::integer) AS right_id',
                [
                    'pid' => $parentRightId,
                    'name' => $name
                ]
            )->fetch()['right_id'];
        }

        $errors = [
            -2 => ['link', $this->t->_('error_link_not_exists')],
            -1 => ['name', $this->t->_('error_name_exists')],
             0 => ['name', $this->t->_('error_name_empty')],
        ];

        $notification = '';

        if ($rightId > 0) {
            $state = 'yes';

            $notification = $this->t->_('right_add_complete');
        }
        else {
            $state = 'no';

            if ($rightId > -2) {
                $status = $errors[$rightId];
                $statusManager->setStatus($status[0], AjaxFormResponse::ERROR, $status[1]);

            }
            else {
                $notification = $this->t->_('error_link_not_exists');
            }
        }

        if ($rightId > 0 && ($title != '' || $description != '')) {
            $this->db->query(
                'SELECT dp_rights_edit(:rid::integer, :lid::integer, :name::varchar(64), :title::varchar(256), :descr::text)',
                [
                    'rid' => $rightId,
                    'lid' => $langId,
                    'name' => $name,
                    'title' => $title,
                    'descr' => $description
                ]
            )->fetch();
        }

        $html = $rightId > 0 ? $this->view->getPartial('rights/partials/rights_explorer_li', [
            'right_node_id' => 0,
            'rights_list' => [
                0 => [
                    [
                        'right_id' => $rightId,
                        'right_name' => $name,
                        'right_title' => $title,
                        'right_in_use' => 0,
                        'right_description' => $description
                    ]
                ]
            ],
            'rights_get_param' => $getParam,
            'rights_lang' => $langId
        ]) : '';


        $this->sendResponseAjax([
            'state'  => $state,
            'notification' => $notification,
            'fields' => $statusManager->getStatuses(),
            'right' => $rightId,
            'html' => $html
        ]);
    }

    protected function apiRight_delete() {
        $rightId = (int)$this->request->getPost('right_id');

        $this->db->query('SELECT dp_rights_delete(:rid::integer)', ['rid' => $rightId])->fetch();

        $this->sendResponseAjax([
            'state'  => 'yes'
        ]);
    }

    protected function apiRight_link()
    {
        $rightId = (int)$this->request->getPost('right_id');
        $isActive = (int)$this->request->getPost('active') ? 1 : 0;

        $groupId = (int)$this->request->get('group_id');
        $memberId = (int)$this->request->get('member_id');

        if ($groupId) {
            $this->db->query(
                'SELECT dp_rights_link_group(:rid::integer, :gid::integer, :active::integer)',
                [
                    'rid' => $rightId,
                    'gid' => $groupId,
                    'active' => $isActive
                ]
            )->fetch();
        }
        else if ($memberId) {
            $this->db->query(
                'SELECT dp_rights_link_member(:rid::integer, :mid::bigint, :active::integer)',
                [
                    'rid' => $rightId,
                    'mid' => $memberId,
                    'active' => $isActive
                ]
            )->fetch();
        }


        $this->sendResponseAjax([
            'state'  => 'yes'
        ]);
    }

    protected function apiRight_link_delegate()
    {
        $rightId = (int)$this->request->getPost('right_id');
        $isActive = (int)$this->request->getPost('active') ? 1 : 0;

        $groupId = (int)$this->request->get('group_id');
        $memberId = (int)$this->request->get('member_id');

        if ($groupId) {
            $this->db->query(
                'SELECT dp_rights_link_group_delegate(:rid::integer, :gid::integer, :active::integer)',
                [
                    'rid' => $rightId,
                    'gid' => $groupId,
                    'active' => $isActive
                ]
            )->fetch();
        }


        $this->sendResponseAjax([
            'state'  => 'yes'
        ]);
    }

    protected function apiRight_link_leader()
    {
        $rightId = (int)$this->request->getPost('right_id');
        $isActive = (int)$this->request->getPost('active') ? 1 : 0;

        $groupId = (int)$this->request->get('group_id');
        $memberId = (int)$this->request->get('member_id');

        if ($groupId) {
            $this->db->query(
                'SELECT dp_rights_link_group_leader(:rid::integer, :gid::integer, :active::integer)',
                [
                    'rid' => $rightId,
                    'gid' => $groupId,
                    'active' => $isActive
                ]
            )->fetch();
        }

        $this->sendResponseAjax([
            'state'  => 'yes'
        ]);
    }

    protected function apiMeta_lang()
    {
        

        $rightId = (int)$this->request->get('right');
        $langId = (int)$this->request->getPost('lang');

        $rightRow = $this->db->query(
            '
                SELECT right_title, right_description 
                FROM rights_list_complete 
                WHERE right_id = :rid::integer AND lang_id = :lid
            ',
            ['rid' => $rightId, 'lid' => $langId]
        )->fetch();

        $this->sendResponseAjax([
            'state'  => 'yes',
            'fields' => [
                ['title', $rightRow['right_title']],
                ['description', $rightRow['right_description']]
            ]
        ]);
    }
}