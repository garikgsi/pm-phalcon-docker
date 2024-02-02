<?php
namespace Site\Rcp\Controllers;

use Core\Builder\Bookmarks;
use Core\Extender\ControllerAppRcp;
use Core\Lib\AjaxFormResponse;
use Core\Lib\PasswordHash;
use Phalcon\Mvc\View;


class MembersController extends ControllerAppRcp
{
    public function initialize()
    {
        parent::initialize();

        $this->view->setLayout('content_wrap');

        $this->breadcrumbs->addCrumb('/members', $this->t->_('member_list_title'), View::LEVEL_LAYOUT);

        $this->hapi->setHapiController('members');
        $this->view->setVar('rcpMainMenuActiveItem', 'rcp_menu_members');
        $this->attachHapiCallback(3, 'defaultMainMenuClick', '', []);
    }

    public function indexAction()
    {
        $membersList = $this->db->query(
            'SELECT * FROM members_list_with_group_lang WHERE lang_id = :lid::integer LIMIT 50',
            ['lid' => $this->user->getLangId()]
        )->fetchAll();

        $this->view->setVar('membersList', $membersList);

        $this->site->setMetaTitle($this->t->_('member_list_title'));
        $this->view->setVar('content_wrap_title', $this->t->_('member_list_title'));
        $this->hapi->setHapiAction('index');
    }

    public function editAction($memberId, $mode = '')
    {
        $Bookmarks = new Bookmarks();

        $t = $this->t;

        $mode   = mb_strtolower($mode);

        $hrefBase = '/members/edit/'.$memberId;

        $n2 = 'groups';
        $n3 = 'rights';

        $Bookmarks->addBookMarksFromArray([
            ['',  $t->_('rcp_member_books_meta'),   $hrefBase,         2, 1, '', ' elzPLT', ' data-bg="blue" data-fn="white"'],
            [$n2, $t->_('rcp_member_books_groups'), $hrefBase.'/'.$n2, 2, 0, '', ' elzPLT', ' data-bg="blue" data-fn="white"'],
            [$n3, $t->_('rcp_member_books_right'),  $hrefBase.'/'.$n3, 2, 0, '', ' elzPLT', ' data-bg="blue" data-fn="white"']
        ]);

        if (!$Bookmarks->isBookMarkExists($mode)) {
            $this->redirect($hrefBase);
            return;
        }

        $Bookmarks->setActive($mode);

        $allowedModes = [
            ''       => ['edit',   'editMember',         'edit'],
            'groups' => ['groups', 'editMemberGroups',   'groups'],
            'rights' => ['rights', 'editMemberRights',   'rights']
        ];

        $memberId = (int)$memberId;

        $memberRow = $this->db->query(
            'SELECT * FROM members_list_with_group_lang WHERE lang_id = :lid::integer AND member_id = :mid',
            ['lid' => $this->user->getLangId(), 'mid' => $memberId]
        )->fetch();

        if (!$memberRow) {
            $this->redirect('members');
        }


        $selectedMode = $allowedModes[$mode];

        $this->hapi->setHapiAction($selectedMode[0]);
        $actionName = $selectedMode[1];
        $this->view->pick('members/'.$selectedMode[2]);


        $this->view->setVar('bookMarks', $Bookmarks->getBookMarks());
        $this->view->setVar('memberRow', $memberRow);

        $this->$actionName($memberRow);


        $this->breadcrumbs->addCrumb('/members/edit/'.$memberId, $this->t->_('member_edit_title'), View::LEVEL_LAYOUT);

        $this->site->setMetaTitle($this->t->_('member_edit_title'));
        $this->view->setVar('content_wrap_title', $this->t->_('member_edit_title'));
        $this->attachHapiCallback(2, 'defaultBookmarksClick', '', []);
    }

    private function editMember($memberRow)
    {

    }

    private function editMemberGroups($memberRow)
    {
        $groupsList = (new GroupsController())->prepareGroupsList($this->db->query(
            '
                SELECT 
                    gl.*,
                    CoALESCE(mgl.member_id, 0) AS group_linked 
                FROM 
                    groups_list AS gl
                    LEFT JOIN members_groups_links AS mgl ON mgl.group_id = gl.group_id AND mgl.member_id = :mid::bigint
                WHERE 
                    gl.lang_id = :lid
            ',
            [
                'lid' => $this->user->getLangId(),
                'mid' => $memberRow['member_id']
            ]
        ));

        $primaryGroups = $this->db->query(
            'SELECT * FROM groups_list WHERE lang_id = :lid ORDER BY group_id',
            [
                'lid' => $this->user->getLangId()
            ]
        );

        $primaryGroupsList = [];

        while($row = $primaryGroups->fetch()) {
            $primaryGroupsList[] = [
                $row['group_id'],
                $row['group_title']
            ];
        }

        $this->view->setVar('groupsList', $groupsList);
        $this->view->setVar('primaryGroupsList', $primaryGroupsList);
    }

    private function editMemberRights($memberRow)
    {

        $this->view->setVar('rightsList', $this->getGroupedRightsList($memberRow['member_id']));
    }


    private function getGroupedRightsList($memberId)
    {
        $rightsList = [
            0 => [
                ['icon' => 'ic-folder', 's_icon' => 'ic-play',  'parent_id' => '0', 'id' => 'app_0',  'title' => 'Права приложений'],
                ['icon' => 'ic-folder', 's_icon' => 'ic-earth', 'parent_id' => '0', 'id' => 'site_0', 'title' => 'Права для сайтов'],
                ['icon' => 'ic-folder', 's_icon' => 'ic-cog',   'parent_id' => '0', 'id' => 'controller_0', 'title' => 'Права контроллеров'],
                ['icon' => 'ic-folder', 's_icon' => 'ic-lock',  'parent_id' => '0', 'id' => 'none_0', 'title' => 'Общие права']
            ]
        ];

        $rights = $this->db->query(
            'SELECT * FROM dp_members_rights_links_list(:mid::bigint, :lid::integer)',
            ['lid' => $this->user->getLangId(), 'mid' => $memberId ]
        );

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
                'right_id' => $row['right_id'],
                'is_member' => 1,
                'given_by_member' => (int)$row['given_by_member'],
                'given_by_group'  => (int)$row['given_by_group'],
                'is_group_leader' => (int)$row['is_group_leader'],
                'is_active' => (int)$row['is_active'],
                'is_inherit' => (int)$row['is_inherit'],
                'right_given_to_leader' => (int)$row['right_given_to_leader']
            ];

            $rightsList[$parentStrId][] = $rightData;
        }

        return $rightsList;
    }



    protected function apiMember_search()
    {
        $searchString = mb_strtolower(trim($this->request->getPost('search')));

        if ($searchString != '') {
            $members = $this->db->query(
                '
                SELECT * FROM members_list_with_group_lang 
                WHERE lang_id = :lid::integer AND member_nick_lower LIKE :nick LIMIT 20
            ',
                [
                    'lid' => $this->user->getLangId(),
                    'nick' => '%'.$searchString.'%'
                ]
            );
        }
        else {
            $members = $this->db->query(
                'SELECT * FROM members_list_with_group_lang WHERE lang_id = :lid::integer LIMIT 50',
                ['lid' => $this->user->getLangId()]
            );
        }

        $html = '';

        while($row = $members->fetch()) {
            $html .= $this->view->getPartial('members/partials/member_tr', $row);
        }

        $this->sendResponseAjax([
            'state'  => 'yes',
            'html' => $html
        ]);
    }

    protected function apiMeta_edit()
    {
        $memberId = (int)$this->request->get('member_id');

        $memberNick = trim($this->request->getPost('nick'));
        $memberGender = $this->request->getPost('gender');

        $nickResult = 0;

        if ($memberNick != '') {
            $nickResult = (int)$this->db->query(
                'SELECT dp_members_change_nick(:mid::bigint, :nick::varchar(100)) AS res',
                ['mid' => $memberId, 'nick' => $memberNick]
            )->fetch()['res'];
        }

        $this->db->query(
            'SELECT dp_members_change_gender(:mid::bigint, :gen::gender) AS res',
            ['mid' => $memberId, 'gen' => $memberGender]
        )->fetch();

        $statusManager = (new AjaxFormResponse())->fillPostWithClear();

        $errors = [
            1 => ['nick', $this->t->_('error_name_empty')],
            2 => ['nick', $this->t->_('error_name_exists')],
        ];

        $state = 'yes';

        if ($memberNick == '') {
            $state = 'no';
            $status = $errors[1];
            $statusManager->setStatus($status[0], AjaxFormResponse::ERROR, $status[1]);

        }
        else if ($nickResult == 2) {
            $state = 'no';
            $status = $errors[2];
            $statusManager->setStatus($status[0], AjaxFormResponse::ERROR, $status[1]);
        }

        $this->sendResponseAjax([
            'state'  => $state,
            'fields' => $statusManager->getStatuses()
        ]);
    }

    protected function apiMember_primary_group()
    {
        $memberId = (int)$this->request->get('member_id');
        $groupId = (int)$this->request->get('group_id');

        $result = (int)$this->db->query(
            '
                SELECT dp_members_change_primary_group(:mid::bigint, :gid::integer) AS res
            ',
            [
                'mid' => $memberId,
                'gid' => $groupId
            ]
        )->fetch()['res'];

        $errorCodes = [
            -1 => $this->t->_('error_primary_group_primary_only'),
            -2 => $this->t->_('error_primary_group_not_exists'),
            -3 => $this->t->_('error_primary_group_not_set')
        ];

        $state = 'yes';
        $message = '';

        if ($result < 0) {
            $state = 'no';
            $message = $errorCodes[$result];
        }


        $this->sendResponseAjax([
            'state'  => $state,
            'message' => $message,
            'result' => $result
        ]);
    }

    protected function apiMember_group_toggle()
    {
        $memberId = (int)$this->request->get('member_id');
        $groupId = (int)$this->request->get('group_id');

        $result = (int)$this->db->query(
            '
                SELECT dp_members_change_group_link_toggle(:mid::bigint, :gid::integer) AS res
            ',
            [
                'mid' => $memberId,
                'gid' => $groupId
            ]
        )->fetch()['res'];

        $errorCodes = [
            -1 => $this->t->_('error_secondary_group_cant_leave'),
            -2 => $this->t->_('error_secondary_group_banned'),
            -3 => $this->t->_('error_primary_group_not_exists'),
            -4 => $this->t->_('error_secondary_group_not_set'),
            -5 => $this->t->_('error_secondary_group_is_primary')
        ];

        $state = 'yes';
        $message = '';

        if ($result < 0) {
            $state = 'no';
            $message = $errorCodes[$result];
        }

        $this->sendResponseAjax([
            'state'  => $state,
            'message' => $message,
            'result' => $result
        ]);
    }

    protected function apiMember_right_toggle()
    {
        $memberId = (int)$this->request->get('member_id');
        $rightId = (int)$this->request->get('right_id');

        $result = (int)$this->db->query(
            '
                SELECT dp_members_change_right_link_toggle(:mid::bigint, :rid::integer) AS res
            ',
            [
                'mid' => $memberId,
                'rid' => $rightId
            ]
        )->fetch()['res'];

        $this->sendResponseAjax([
            'state'  => 'yes',
            'message' => '',
            'result' => $result
        ]);
    }

    protected function apiMember_change_email_password()
    {
        $memberId = (int)$this->request->get('member_id');
        $pass1 = trim($this->request->getPost('pass1'));
        $pass2 = trim($this->request->getPost('pass2'));
        $mail1 = mb_strtolower(trim($this->request->getPost('mail1')));
        $mail2 = mb_strtolower(trim($this->request->getPost('mail2')));

        $email1 = preg_replace("/\s+/", '', htmlspecialchars($mail1));
        $email2 = preg_replace("/\s+/", '', htmlspecialchars($mail2));

        $hasErrors = 0;

        $responseMessage = '';

        $statusManager = new AjaxFormResponse();

        if(!preg_match( "/^.+@.+\..+$/ui", $email1)) {
            $statusManager->setStatus('mail1', AjaxFormResponse::ERROR, 'Введенный адрес не является имейлом');
            $hasErrors = 1;
        }

        if(!preg_match( "/^.+@.+\..+$/ui", $email2)) {
            $statusManager->setStatus('mail2', AjaxFormResponse::ERROR, 'Введенный адрес не является имейлом');
            $hasErrors = 1;
        }

        if ($email1 != $email2) {
            $statusManager->setStatus('mail1', AjaxFormResponse::ERROR, 'Необходимо указать одинаковые имейлы');
            $statusManager->setStatus('mail2', AjaxFormResponse::ERROR, 'Необходимо указать одинаковые имейлы');
            $hasErrors = 1;
        }

        if (!$hasErrors && mb_strlen($email1) > 98) {
            $statusManager->setStatus('mail1', AjaxFormResponse::ERROR, 'Имейл должен быть короче 100 символов');
            $statusManager->setStatus('mail2', AjaxFormResponse::ERROR, 'Имейл должен быть короче 100 символов');
            $hasErrors = 1;
        }

        if (mb_strlen($pass1) < 5 || mb_strlen($pass2) < 5) {
            $statusManager->setStatus('pass1', AjaxFormResponse::ERROR, 'Пароль не может быть короче пяти символов');
            $statusManager->setStatus('pass2', AjaxFormResponse::ERROR, 'Пароль не может быть короче пяти символов');
            $hasErrors = 1;
        }


        if ($pass1 != $pass2) {
            $statusManager->setStatus('pass1', AjaxFormResponse::ERROR, 'Пароли должны совпадать');
            $statusManager->setStatus('pass2', AjaxFormResponse::ERROR, 'Пароли должны совпадать');
            $hasErrors = 1;
        }

        if ($hasErrors == 0) {
            $salt = PasswordHash::passGenSalt();
            $hash = PasswordHash::passGenHash($pass1, $mail1, $salt);

            $resultId = (int)$this->db->query(
                'SELECT dp_members_change_password_and_email(:mid::bigint, :salt::varchar(64), :hash::hash_md5, :mail::email) AS res',
                [
                    'mid' => $memberId,
                    'salt' => $salt,
                    'hash' => $hash,
                    'mail' => $mail1
                ]
            )->fetch()['res'];

            if ($resultId == 2) {
                $statusManager->setStatus('mail1', AjaxFormResponse::ERROR, 'Такой имейл уже занят');
                $statusManager->setStatus('mail2', AjaxFormResponse::ERROR, 'Такой имейл уже занят');
                $hasErrors = 1;
            }
            else if ($resultId == 0) {
                $hasErrors = 0;

                $responseMessage = 'Данные измменены';

                $statusManager->fillPostWithSuccess();
            }
        }

        $this->sendResponseAjax([
            'notification' => $responseMessage,
            'status' => $hasErrors ? 'no' : 'yes',
            'fields' => $statusManager->getStatuses()
        ]);
    }
}