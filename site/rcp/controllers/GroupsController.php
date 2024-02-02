<?php
namespace Site\Rcp\Controllers;

use Core\Builder\Bookmarks;
use Core\Extender\ControllerAppRcp;
use Core\Lib\AjaxFormResponse;
use Phalcon\Filter;
use Phalcon\Mvc\View;


class GroupsController extends ControllerAppRcp
{
    public function initialize()
    {
        parent::initialize();

        $this->view->setLayout('content_wrap');

        $this->breadcrumbs->addCrumb('/groups', $this->t->_('group_title'), View::LEVEL_LAYOUT);

        $this->hapi->setHapiController('groups');
        $this->view->setVar('rcpMainMenuActiveItem', 'rcp_menu_groups');
        $this->attachHapiCallback(3, 'defaultMainMenuClick', '', []);
    }


    public function indexAction()
    {

        $groupsList = $this->prepareGroupsList($this->db->query(
            'SELECT * FROM groups_list WHERE lang_id = :lid',
            ['lid' => $this->user->getLangId()]
        ));

        $this->buttonsForAdd();

        $this->view->setVar('groupsList', $groupsList);

        $this->site->setMetaTitle($this->t->_('group_title'));
        $this->view->setVar('content_wrap_title', $this->t->_('group_title'));
        $this->hapi->setHapiAction('index');
    }

    public function addAction($parentId = 0)
    {
        $this->view->setVar('selectList', $this->prepareGroupsSelect());
        $this->view->setVar('group_parent_id', (int)$parentId);

        $this->breadcrumbs->addCrumb('/groups/add/', $this->t->_('rcp_groups_add_title'), View::LEVEL_LAYOUT);

        $this->site->setMetaTitle($this->t->_('rcp_groups_add_title'));
        $this->view->setVar('content_wrap_title', $this->t->_('rcp_groups_add_title'));
        $this->hapi->setHapiAction('add');
    }

    public function editAction($groupId, $mode = '')
    {
        $Bookmarks = new Bookmarks();

        $t = $this->t;

        $mode   = mb_strtolower($mode);

        $hrefBase = '/groups/edit/'.$groupId;

        $n2 = 'rights';
        $n3 = 'members';

        $Bookmarks->addBookMarksFromArray([
            ['',  $t->_('rcp_groups_books_meta'),    $hrefBase,         2, 1, '', ' elzPLT', ' data-bg="blue" data-fn="white"'],
            [$n2, $t->_('rcp_groups_books_rights'),  $hrefBase.'/'.$n2, 2, 0, '', ' elzPLT', ' data-bg="blue" data-fn="white"']/*,
            [$n3, $t->_('rcp_groups_books_members'), $hrefBase.'/'.$n3, 2, 0, '', ' elzPLT', ' data-bg="blue" data-fn="white"']*/
        ]);

        if (!$Bookmarks->isBookMarkExists($mode)) {
            $this->redirect($hrefBase);
            return;
        }

        $Bookmarks->setActive($mode);

        $allowedModes = [
            ''        => ['edit',    'editGroup',        'edit'],
            'rights'  => ['rights',  'editGroupRights',  'rights'],
            'members' => ['members', 'editGroupMembers', 'members']
        ];

        $groupId = (int)$groupId;

        $groupRow = $this->db->query(
            'SELECT * FROM groups_list WHERE lang_id = :lid AND group_id = :gid',
            ['lid' => 1, 'gid' => $groupId]
        )->fetch();

        if (!$groupRow) {
            $this->redirect('groups');
            return;
        }


        $this->buttonsForAdd($groupId);

        $selectedMode = $allowedModes[$mode];

        $this->hapi->setHapiAction($selectedMode[0]);
        $actionName = $selectedMode[1];
        $this->view->pick('groups/'.$selectedMode[2]);


        $this->view->setVar('bookMarks', $Bookmarks->getBookMarks());
        $this->view->setVar('groupRow', $groupRow);


        $this->hapi->setHapiAction('edit');
        $this->$actionName($groupRow);

        $this->breadcrumbs->addCrumb('/groups/edit/'.$groupId, $this->t->_('group_edit_title'), View::LEVEL_LAYOUT);
        $this->breadcrumbs->addCrumb('/groups/edit/'.$groupId, $groupRow['group_title'], View::LEVEL_LAYOUT);

        $this->site->setMetaTitle($this->t->_('group_edit_title'));
        $this->view->setVar('content_wrap_title', $this->t->_('group_edit_title').': '.$groupRow['group_title']);
        $this->attachHapiCallback(2, 'defaultBookmarksClick', '', []);
    }


    private function editGroup($groupRow)
    {
        $groupId = (int)$groupRow['group_id'];

        $this->view->setVar('group_members', $this->db->query(
            'SELECT dp_groups_calculate_members(:gid::integer) AS res',
            ['gid' => $groupId]
        )->fetch()['res']);

        $this->view->setVar('group_groups', $this->db->query(
            'SELECT dp_groups_calculate_groups(:gid::integer) AS res',
            ['gid' => $groupId]
        )->fetch()['res']);

        $this->view->setVar('group_rights', $this->db->query(
            'SELECT dp_groups_calculate_rights(:gid::integer) AS res',
            ['gid' => $groupId]
        )->fetch()['res']);

        $this->view->setVar('selectList', $this->prepareGroupsSelect($groupId));


        $groupsList = $this->prepareGroupsList($this->db->query(
            '
                SELECT 
                    gl.*
                FROM 
                    groups_list AS gl, 
                    groups_relationships AS gr 
                WHERE 
                    gl.lang_id = :lid AND 
                    gr.group_id = gl.group_id AND  
                    gr.parent_id = :gid AND
                    gr.group_id <> :gid  
            ',
            [
                'lid' => $this->user->getLangId(),
                'gid' => $groupId
            ]
        ));

        $this->view->setVar('groupsList', $groupsList);
    }

    private function editGroupRights($groupRow)
    {
        $this->hapi->setHapiAction('rights');
        $this->view->setVar('rightsList', (new RightsController())->getRightsForGroup($groupRow['group_id']));
    }

    private function editGroupMembers($groupRow)
    {
        $this->view->setVar('members_list', $this->getMembersList($groupRow['group_id']));
    }

    public function prepareGroupsList(\Phalcon\Db\ResultInterface $dbResult)
    {
        $groupsList = [];

        while($row = $dbResult->fetch()) {
            $parentId = (int)$row['group_parent_id'];

            $row['group_parent_id'] = $parentId;

            if (! isset($groupsList[$parentId])) {
                $groupsList[$parentId] = [];
            }

            $row['group_system'] = (int)$row['group_system'];
            $row['group_leader_id'] = (int)$row['group_leader_id'];

            $groupsList[$parentId][] = $row;
        }

        return $groupsList;
    }

    private function prepareGroupsSelect($groupId = 0)
    {
        $groups = $this->db->query(
            'SELECT * FROM groups_list WHERE lang_id = :lid',
            ['lid' => $this->user->getLangId()]
        );

        $groupsList = [];

        while($row = $groups->fetch()) {
            $parentId = (int)$row['group_parent_id'];

            $row['group_parent_id'] = $parentId;

            if (! isset($groupsList[$parentId])) {
                $groupsList[$parentId] = [];
            }

            $row['group_system'] = (int)$row['group_system'];
            $row['group_leader_id'] = (int)$row['group_leader_id'];

            $groupsList[$parentId][] = $row;
        }

        $selectList = [[0, 'Нет родителя'], ['label' => 'Список групп', 'list' => []]];

        $prepareSelectList = function($nodeId, $level = 0) use($groupsList, $groupId, &$selectList, &$prepareSelectList) {
            if (!isset($groupsList[$nodeId])) {
                return;
            }

            $prefix = str_pad('', $level * 2, '-');

            for($a = 0, $len = sizeof($groupsList[$nodeId]); $a < $len; $a++) {
                $group = $groupsList[$nodeId][$a];

                $localGroupId = (int)$group['group_id'];

                if ($groupId && $groupId == $localGroupId) {
                    continue;
                }

                $selectList[1]['list'][] = [$localGroupId, $prefix . $group['group_title']];

                $prepareSelectList($localGroupId, $level + 1);
            }
        };

        $prepareSelectList(0, 0);

        return $selectList;
    }

    private function buttonsForAdd($groupId = 0) {
        $buttons = [];

        $buttons[] = [
            'text'    => $this->t->_('rcp_groups_add'),
            'href'    => '/groups/add',
            'variant' => 'send',
            'title'   => $this->t->_('rcp_groups_add_d'),
            'icon'    => '',
            'id'      => '',
            'classes' => 'elizaHApi',
            'data'    => ['data-level' => 3]
        ];

        if ($groupId) {
            $buttons[] = [
                'text'    => $this->t->_('rcp_groups_add_parent'),
                'href'    => '/groups/add/'.$groupId,
                'variant' => 'success',
                'title'   => $this->t->_('rcp_groups_add_parent_d'),
                'icon'    => '',
                'id'      => '',
                'classes' => 'elizaHApi',
                'data'    => ['data-level' => 3]
            ];
        }

        $this->view->setVar('content_wrap_title_buttons', $buttons);
    }

    private function getMembersList($groupId, $searchString = '')
    {
        $searchAnd = '';
        $searchObject = [
            'gid' => $groupId,
            'like' => '%'.$searchString.'%'
        ];

        if ($searchString != '') {
            $searchAnd = 'AND m.member_nick_lower LIKE :like';

            $searchObject['like'] = '%'.$searchString.'%';
        }

        return $this->db->query(
            '
                SELECT
                    m.member_id,
                    m.member_gender,
                    m.member_email,
                    m.member_nick,
                    m.member_date_register
                FROM members AS m 
                WHERE m.member_group = :gid::integer '.$searchAnd.'                    
                LIMIT 25
            ',
            $searchObject
        )->fetchAll();
    }

    protected function apiAdd()
    {
        $parentId = (int)$this->request->getPost('parent');
        $langId = (int)$this->request->getPost('lang');

        $name = trim($this->request->getPost('name'));
        $title = trim($this->request->getPost('title'));

        $groupId = (int)$this->db->query(
            'SELECT dp_groups_add(:name::varchar(64), :pid::int, :lid::int, :title::varchar(64)) AS res',
            [
                'pid' => $parentId,
                'lid' => $langId,
                'name' => $name,
                'title' => $title
            ]
        )->fetch()['res'];

        $statusManager = (new AjaxFormResponse())->fillPostWithClear();

        $errors = [
            0 => ['name', $this->t->_('error_name_empty')],
            -1 => ['name', $this->t->_('error_name_exists')],
        ];


        if ($groupId <= 0) {
            $status = $errors[$groupId];
            $statusManager->setStatus($status[0], AjaxFormResponse::ERROR, $status[1]);
        }

        $this->sendResponseAjax([
            'state'  => $groupId <= 0 ? 'no' : 'yes',
            'fields' => $statusManager->getStatuses(),
            'group' => $groupId
        ]);
    }

    protected function apiMeta_edit()
    {
        $groupId = (int)$this->request->get('group');
        $parentId = (int)$this->request->getPost('parent');
        $langId = (int)$this->request->getPost('lang');

        $name = trim($this->request->getPost('name'));
        $title = trim($this->request->getPost('title'));

        $result = (int)$this->db->query('
            SELECT dp_groups_edit(:gid::int, :name::varchar(64), :pid::int, :lid::int, :title::varchar(64)) AS res',
            [
                'gid' => $groupId,
                'lid' => $langId,
                'pid' => $parentId,
                'name' => $name,
                'title' => $title
            ]
        )->fetch()['res'];


        $statusManager = (new AjaxFormResponse())->fillPostWithClear();

        $errors = [
            2 => ['name', $this->t->_('error_name_empty')],
            3 => ['name', $this->t->_('error_name_exists')],
        ];

        if ($result > 0) {
            $status = $errors[$result];
            $statusManager->setStatus($status[0], AjaxFormResponse::ERROR, $status[1]);
        }

        $this->sendResponseAjax([
            'state'  => $result <= 0 ? 'yes' : 'no',
            'fields' => $statusManager->getStatuses(),
            'reload' => $result == -1 ? 1 : 0
        ]);
    }

    protected function apiMeta_lang()
    {


        $groupId = (int)$this->request->get('group');
        $langId = (int)$this->request->getPost('lang');

        $this->sendResponseAjax([
            'state'  => 'yes',
            'fields' => [
                ['title', $this->db->query(
                    'SELECT group_title FROM groups_list WHERE group_id = :gid AND lang_id = :lid',
                    ['gid' => $groupId, 'lid' => $langId]
                )->fetch()['group_title']]
            ]
        ]);

    }

    protected function apiMember_search()
    {
        $this->getMembersList($this->request->getPost('group_id'), $this->request->getPost('search'));
    }
}
