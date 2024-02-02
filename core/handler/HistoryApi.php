<?php
namespace Core\Handler;

use Phalcon\Di\Injectable;
use Phalcon\Mvc\View;
/**
 * @property \Core\Handler\Site site
 */
class HistoryApi extends Injectable
{
    private static $instance = null;

    private $data = [
        'hapi' => [
            'site'       => '', // Нейм сайта
            'controller' => 'index', // Контроллер сайта
            'action'     => 'index'  // Экшен в контроллере
        ],
        'render' => [
            'container' => '', // Идентификатор контейнера, в который надо вставлять HTML
            'level'     => ''  // Уровень рендеринга: 1 - action, 2 - before, 3 - layout, 4 - after, 5 - main
        ],
        'callbacks' => [],
        'html'      => ''
    ];

    private $renderLevelAlias = [
        'off'    => View::LEVEL_NO_RENDER,
        'action' => View::LEVEL_ACTION_VIEW,
        'before' => View::LEVEL_BEFORE_TEMPLATE,
        'layout' => View::LEVEL_LAYOUT,
        'after'  => View::LEVEL_AFTER_TEMPLATE,
        'main'   => View::LEVEL_MAIN_LAYOUT
    ];

    const HTML_ID_RENDER_CONTAINER = 'ipEnRenderContainerLevel';



    public function __construct($siteName) {
        if (self::$instance) {
            return self::$instance;
        }

        $this->data['hapi']['site'] = $siteName;

        self::$instance = $this;

        return $this;
    }

    public function setHapiController($controller) {
        $this->data['hapi']['controller'] = $controller;

        return $this;
    }

    public function setHapiAction($action) {
        $this->data['hapi']['action'] = $action;

        return $this;
    }

    public function getSite() {
        return $this->data['hapi']['site'];
    }

    public function getController() {
        return $this->data['hapi']['controller'];
    }

    public function getAction() {
        return $this->data['hapi']['action'];
    }

    public function genRenderContainerId($renderLevel) {
        return self::HTML_ID_RENDER_CONTAINER.''.$this->renderLevelAlias[$renderLevel];
    }

    public function addCallback($funcName, $scope, $data = []) {
        $this->data['callbacks'][] = [
            'func'  => $funcName,
            'scope' => $scope,
            'data'  => $data
        ];

        return $this;
    }

    public function sendHapiResponse($html) {
        $this->setCurrentRenderLevel();

        $data = $this->data;

        $data['html'] = $html;
        $data['meta'] = $this->site->getMeta();

        $this->response->setJsonContent(['status' => 'success', 'data' => $data]);
        $this->response->send();
    }


    private function setCurrentRenderLevel() {
        $level = $this->view->getRenderLevel();

        $this->data['render']['level']    = $level;
        $this->data['render']['container'] = self::HTML_ID_RENDER_CONTAINER.''.$level;
    }
}