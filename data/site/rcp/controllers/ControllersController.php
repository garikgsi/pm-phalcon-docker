<?php
namespace Site\Rcp\Controllers;

use Core\Extender\ControllerAppRcp;
use Core\Lib\AjaxFormResponse;
use Phalcon\Filter;
use Phalcon\Mvc\View;


class ControllersController extends ControllerAppRcp
{
    public function initialize() {
        parent::initialize();

        $this->view->setLayout('content_wrap');

        $this->breadcrumbs->addCrumb('/controllers', $this->t->_('ctrl_title'), View::LEVEL_LAYOUT);

        $this->hapi->setHapiController('controllers');
        $this->view->setVar('rcpMainMenuActiveItem', 'rcp_menu_controllers');
        $this->attachHapiCallback(3, 'defaultMainMenuClick', '', []);
    }

    public function indexAction() {
        $controllersList = $this->db->query(
            'SELECT * FROM sites_controllers_list WHERE lang_id = :lid',
            ['lid' => $this->user->getLangId()]
        )->fetchAll();

        $this->site->setMetaTitle($this->t->_('ctrl_title'));
        $this->view->setVar('content_wrap_title', $this->t->_('ctrl_title'));
        $this->view->setVar('controllersList', $controllersList);

        $this->attachHapiCallback(3, 'defaultMainMenuClick', '', []);
    }

    public function editAction($controllerId = 0)
    {
        $controllerId = (int)$controllerId;

        $this->view->setVar('content_wrap_title', $this->t->_('ctrl_controllers_edit'));
        $this->breadcrumbs->addCrumb('/controllers/edit/'.$controllerId, $this->t->_('ctrl_controllers_edit'), View::LEVEL_LAYOUT);
        $this->hapi->setHapiAction('edit');

        $controllerRow = $this->db->query(
            'SELECT * FROM sites_controllers_list WHERE lang_id = :lid AND controller_id = :cid',
            ['lid' => 1, 'cid' => $controllerId]
        )->fetch();

        if (!$controllerRow) {
            $this->redirect('controllers');
            return;
        }

        $sitesList = $this->db->query(
            'SELECT * FROM sites_controllers_linked_to_sites WHERE controller_id = :cid',
            ['cid' => $controllerId]
        )->fetchAll();

        $this->view->setVar('controllerRow', $controllerRow);
        $this->view->setVar('sitesList', $sitesList);
        $this->view->setVar('rightsList', (new RightsController())->getRightsForController($controllerId));

        $this->attachHapiCallback(3, 'defaultMainMenuClick', '', []);
    }


    protected function apiController_add()
    {


        $name = mb_strtolower(trim($this->request->getPost('name')));

        $addState   = 'yes';

        $errors = [
            0 => ['name', $this->t->_('error_controller_exists')]
        ];

        $controllerId = $this->db->query(
            'SELECT dp_sites_controllers_add(:name) AS res',
            ['name' => $name]
        )->fetch()['res'];

        $statusManager = (new AjaxFormResponse())->fillPostWithClear();

        if ($controllerId == 0) {
            $addState   = 'no';

            $status = $errors[0];

            $statusManager->setStatus($status[0], AjaxFormResponse::ERROR, $status[1]);
        }

        $this->sendResponseAjax([
            'state'  => $addState,
            'cid'    => $controllerId,
            'fields' => $statusManager->getStatuses(),
            'html' => $addState == 'yes' ? $this->view->getPartial('controllers/controller_tr', [
                'controller_id' => $controllerId,
                'controller_name' => $name,
                'sites_count' => 0,
                'controller_title' => '',
                'controller_locked' => ''
            ]) : ''
        ]);
    }

    protected function apiController_edit()
    {
        $controllerId = (int)$this->request->get('controller_id');
        $langId = (int)$this->request->getPost('lang');

        $name = mb_strtolower(trim($this->request->getPost('name')));
        $title = trim($this->request->getPost('title'));
        $description = trim($this->request->getPost('description'));

        $this->db->query(
            'SELECT dp_sites_controllers_edit(:cid::integer, :lid::integer, :name::varchar(128), :title::varchar(256), :descr::text)',
            [
                'cid' => $controllerId,
                'lid' => $langId,
                'name' => $name,
                'title' => $title,
                'descr' => $description
            ]
        )->fetch();

        $this->sendResponseAjax(['state'  => 'yes']);
    }

    protected function apiController_meta_lang()
    {


        $projectId = (int)$this->request->get('controller_id');
        $langId    = (int)$this->request->getPost('lang');

        $projectRow = $this->db->query(
            '
              SELECT 
                  controller_name, 
                  controller_title, 
                  controller_description
              FROM 
                sites_controllers_list AS scl                 
              WHERE 
                scl.controller_id  = :cid AND
                scl.lang_id     = :lid    
            
            ',
            [
                'cid' => $projectId,
                'lid' => $langId
            ]
        )->fetch();

        $this->sendResponseAjax([
            'state'  => 'yes',
            'fields' => [
                ['name',  $projectRow['controller_name']],
                ['title', $projectRow['controller_title']],
                ['description', $projectRow['controller_description']]
            ]
        ]);
    }

    protected function apiController_right_add()
    {


        $controllerId = (int)$this->request->get('controller_id');

        $name = trim($this->request->getPost('name'));

        $result = $this->db->query(
            'SELECT dp_rights_add_to_controller(:cid::integer, :name::varchar(64) AS result',
            [
                'cid' => $controllerId,
                'name' => $name
            ]
        )->fetch()['result'];

        $this->sendResponseAjax([
            'state' => $result > 0 ? 'yes' : 'no',
            'html' => $result > 0 ? $this->view->getPartial('controllers/right_tr', [
                'right_id' => $result,
                'right_name' => $name,
                'right_title' => '',
                'right_description' => ''
            ]) : ''
        ]);
    }
}