<?php
namespace Site\Rcp\Controllers;

use Core\Engine\Mustache;
use Core\Extender\ControllerAppRcp;
use Core\Lib\AjaxFormResponse;
use Phalcon\Filter;
use Phalcon\Mvc\View;


class AppsController extends ControllerAppRcp
{
    public function initialize()
    {
        parent::initialize();

        $this->view->setLayout('content_wrap');

        $this->breadcrumbs->addCrumb('/apps', $this->t->_('app_title'), View::LEVEL_LAYOUT);

        $this->hapi->setHapiController('apps');
        $this->view->setVar('rcpMainMenuActiveItem', 'rcp_menu_apps');
        $this->attachHapiCallback(3, 'defaultMainMenuClick', '', []);
    }

    public function indexAction()
    {
        $appsList = $this->db->query(
            'SELECT * FROM apps_list WHERE lang_id = :lid',
            ['lid' => $this->user->getLangId()]
        )->fetchAll();


        $this->view->setVar('appsList', $appsList);

        $this->site->setMetaTitle($this->t->_('app_title'));
        $this->view->setVar('content_wrap_title', $this->t->_('app_title'));
        $this->hapi->setHapiAction('index');
    }


    public function editAction($appId)
    {
        $appId = (int)$appId;

        $appRow = $this->db->query(
            'SELECT * FROM apps_list WHERE app_id = :aid AND lang_id = :lid',
            ['lid' => 1, 'aid' => $appId]
        )->fetch();

        if (!$appRow) {
            $this->redirect('apps');
            return;
        }


        $sitesList = $this->db->query(
            'SELECT * FROM apps_access_sites_with_links WHERE app_id = :aid',
            ['aid' => $appId]
        )->fetchAll();

        $groupsList = $this->db->query(
            'SELECT * FROM apps_access_groups_with_links WHERE lang_id = :lid AND app_id = :aid',
            ['lid' => $this->user->getLangId(), 'aid' => $appId]
        )->fetchAll();

        $membersList = $this->db->query(
            'SELECT * FROM apps_access_members_with_links WHERE app_id = :aid',
            ['aid' => $appId]
        )->fetchAll();


        $this->view->setVar('sitesList', $sitesList);
        $this->view->setVar('groupsList', $groupsList);
        $this->view->setVar('membersList', $membersList);
        $this->view->setVar('rightsList', (new RightsController())->getRightsForApp($appId));

        $this->view->setVar('appRow', $appRow);


        $this->site->setMetaTitle($this->t->_('app_edit'));
        $this->view->setVar('content_wrap_title', $this->t->_('app_edit'));
        $this->breadcrumbs->addCrumb('/apps/edit/'.$appId, $this->t->_('app_edit'), View::LEVEL_LAYOUT);
        $this->hapi->setHapiAction('edit');
    }

    protected function apiApp_add()
    {


        $name = mb_strtolower(trim($this->request->getPost('name')));

        $addState   = 'yes';

        $errors = [
            0 => ['name', $this->t->_('error_app_exists')]
        ];

        $appId = $this->db->query(
            'SELECT dp_apps_add(:name) AS res',
            ['name' => $name]
        )->fetch()['res'];

        $statusManager = (new AjaxFormResponse())->fillPostWithClear();

        if ($appId == 0) {
            $addState   = 'no';

            $status = $errors[0];

            $statusManager->setStatus($status[0], AjaxFormResponse::ERROR, $status[1]);
        }

        $this->sendResponseAjax([
            'state'  => $addState,
            'aid'    => $appId,
            'fields' => $statusManager->getStatuses(),
            'html' => $addState == 'yes' ? $this->view->getPartial(
                'apps/app_tr',
                $this->db->query(
                    'SELECT * FROM apps_list WHERE app_id = :aid AND lang_id = 1',
                    ['aid' => $appId]
                )->fetch()
            ) : ''
        ]);
    }

    protected function apiApp_edit()
    {
        $appId = (int)$this->request->get('app_id');
        $langId = (int)$this->request->getPost('lang');

        $name = mb_strtolower(trim($this->request->getPost('name')));
        $title = trim($this->request->getPost('title'));
        $slogan = trim($this->request->getPost('slogan'));
        $description = trim($this->request->getPost('description'));

        $this->db->query(
            'SELECT dp_apps_edit(:aid::integer, :lid::integer, :name::varchar(32), :title::varchar(128), :slogan::varchar(256), :descr::text)',
            [
                'aid' => $appId,
                'lid' => $langId,
                'name' => $name,
                'title' => $title,
                'slogan' => $slogan,
                'descr' => $description
            ]
        )->fetch();

        $this->sendResponseAjax(['state'  => 'yes']);
    }

    protected function apiApp_meta_lang()
    {


        $appId = (int)$this->request->get('app_id');
        $langId = (int)$this->request->getPost('lang');

        $appRow = $this->db->query(
            '
              SELECT 
                  app_name, 
                  app_title, 
                  app_slogan, 
                  app_description
              FROM 
                apps_list AS al                 
              WHERE 
                al.app_id = :aid AND
                al.lang_id = :lid    
            
            ',
            [
                'aid' => $appId,
                'lid' => $langId
            ]
        )->fetch();

        $this->sendResponseAjax([
            'state'  => 'yes',
            'fields' => [
                ['name',  $appRow['app_name']],
                ['title', $appRow['app_title']],
                ['slogan', $appRow['app_slogan']],
                ['description', $appRow['app_description']]
            ]
        ]);
    }

    protected function apiAccess_mask()
    {
        $appId = (int)$this->request->getPost('app_id');
        $accessMask = $this->request->getPost('mask');

        $this->db->query(
            'UPDATE apps SET app_access = :mask::char(3) WHERE app_id = :aid',
            ['aid' => $appId, 'mask' => $accessMask]
        );

        $this->sendResponseAjax(['state'  => 'yes']);
    }

    protected function apiSites_all()
    {
        $appId = (int)$this->request->getPost('app_id');
        $allSites = (int)$this->request->getPost('all');

        $this->db->query(
            'UPDATE apps SET app_all_sites = :all WHERE app_id = :aid',
            ['aid' => $appId, 'all' => $allSites ? 0 : 1]
        );

        $this->sendResponseAjax(['state'  => 'yes']);
    }

    protected function apiGroup_toggle()
    {
        $appId = (int)$this->request->getPost('app_id');
        $groupId = (int)$this->request->getPost('group_id');

        $action = mb_strtolower($this->request->getPost('action')) == 'link' ? 'link' : 'unlink';

        $this->db->query(
            'SELECT dp_apps_access_groups_'.$action.'(:aid, :gid)',
            ['aid' => $appId, 'gid' => $groupId]
        );

        $this->sendResponseAjax(['state'  => 'yes']);
    }

    protected function apiSite_toggle()
    {
        $appId = (int)$this->request->getPost('app_id');
        $siteId = (int)$this->request->getPost('site_id');

        $action = mb_strtolower($this->request->getPost('action')) == 'link' ? 'link' : 'unlink';

        $this->db->query(
            'SELECT dp_apps_access_sites_'.$action.'(:aid, :sid)',
            ['aid' => $appId, 'sid' => $siteId]
        );

        $this->sendResponseAjax(['state'  => 'yes']);
    }

    protected function apiMember_toggle()
    {
        $appId = (int)$this->request->getPost('app_id');
        $memberId = (int)$this->request->getPost('member_id');

        $action = mb_strtolower($this->request->getPost('action')) == 'link' ? 'link' : 'unlink';

        $this->db->query(
            'SELECT dp_apps_access_members_'.$action.'(:aid, :mid::bigint)',
            ['aid' => $appId, 'mid' => $memberId]
        );

        $html = '';

        if ($action == 'link') {
            $html = $this->view->getPartial('apps/member_tr', $this->db->query(
                'SELECT * FROM apps_access_members_with_links WHERE app_id = :aid AND member_id = :mid',
                ['aid' => $appId, 'mid' => $memberId]
            )->fetch());
        }

        $this->sendResponseAjax(['state'  => 'yes', 'html' => $html]);
    }

    protected function apiMember_search()
    {
        $appId = (int)$this->request->getPost('app_id');
        $search = mb_strtolower(trim($this->request->getPost('search')));

        if ($search == '') {
            $this->sendResponseAjax([
                'state' => 'yes',
                'html' => ''
            ]);
        }


        $membersList = $this->db->query(
            '
                SELECT 
                    \'rcpAppsEditMemberClick\' AS ac_class,
                    member_id, 
                    member_gender, 
                    member_nick 
                FROM 
                    dp_apps_member_search(:aid::integer, :search::varchar(100))
                LIMIT 7
            ',
            [
                'aid' => $appId,
                'search' => $search
            ]
        )->fetchAll();

        $html = '';

        if (sizeof($membersList)) {
            $html = Mustache::renderWithBinds('common/auto_complete_users_list', ['members_list' => $membersList]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'html' => $html
        ]);
    }
}