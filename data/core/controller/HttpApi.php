<?php
namespace Core\Controller;

use Phalcon\Mvc\Micro;
use Phalcon\Mvc\Micro\LazyLoader;
use ReflectionProperty;

class HttpApi
{
    private static $instance;

    private $requestParts = [];

    private $moduleName;
    private $moduleDir;
    private $modulePath;

    //  /api/module/controller/method
    // 0 1   2      3          4
    const MINIMUM_PARTS_COUNT = 5;

    public function __construct() {
        if (self::$instance) {
            return self::$instance;
        }

        $this->parseRequestUri();

        self::$instance = $this;
    }

    private function parseRequestUri() {
        $uriCleaned = preg_replace('/[^A-Za-z0-9\-\/]/', '', $_SERVER['REQUEST_URI']);
        $this->requestParts = explode('/', $uriCleaned);

    }

    public function validateRequest() {
        if (sizeof($this->requestParts) < 5) {
            throw new \Exception('Invalid API uri - too short');
        }

        $moduleName = strtolower($this->requestParts[2]);
        $modulePath = $this->configModulePath($moduleName);

        if (!is_file($modulePath)) {
            throw new \Exception('Invalid API uri - no such module: ' . $modulePath);
        }

        $this->moduleName = $moduleName;
        $this->moduleDir  = $this->configModuleDir($moduleName);
        $this->modulePath = $modulePath;
    }

    private function configModulePath($moduleName) {
        return DIR_API.$moduleName.'Module.php';
    }

    private function configModuleDir($moduleName) {
        return DIR_API.$moduleName.'/';
    }


    public function getModuleDir() {
        return $this->moduleDir;
    }

    public function getModuleNamespace() {
        return 'Api\\'.ucfirst($this->moduleName);
    }

    public function includeModule(Micro &$App) {
        $executeModule = require_once($this->modulePath);
        $executeModule($App);
    }

    public function initializeModule(Micro &$App) {
        $handler = $App->getActiveHandler();

        // Нет ссылки на контроллер удоавлетворяющий выбранному роуту
        if (!(isset($handler[0]) && $handler[0] instanceof LazyLoader)) {
            throw new \Exception('Invalid API uri - no such controller');
        }

        $reflector = new ReflectionProperty($handler[0], 'definition');

        $reflector->setAccessible(true);

        $controllerName = $reflector->getValue($handler[0]);


        (new $controllerName())->initialize();
    }
}