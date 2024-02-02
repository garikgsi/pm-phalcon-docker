<?php
namespace Core\Extender;

use Phalcon\Loader;
use Phalcon\Di\DiInterface;
use Phalcon\Mvc\ModuleDefinitionInterface;

class ModuleSite implements ModuleDefinitionInterface
{
    protected $appBase = '';
    protected $appName = '';

    /**
     * Register a specific autoloader for the module
     * @param DiInterface|null $dependencyInjector
     */
    public function registerAutoloaders(DiInterface $dependencyInjector = NULL)
    {
        $loader = new Loader();

        $loader->registerNamespaces(
            [
                $this->appBase . '\\' . $this->appName . '\\Controllers' => '../' . strtolower($this->appBase) . '/' . strtolower($this->appName) . '/controllers/',
                $this->appBase . '\\' . $this->appName . '\\Handlers' => '../' . strtolower($this->appBase) . '/' . strtolower($this->appName) . '/handlers/',
                $this->appBase . '\\' . $this->appName . '\\Models' => '../' . strtolower($this->appBase) . '/' . strtolower($this->appName) . '/models/',
                $this->appBase . '\\' . $this->appName . '\\Libs' => '../' . strtolower($this->appBase) . '/' . strtolower($this->appName) . '/libs/'
            ]
        );

        $loader->register();
    }

    /**
     * Register specific services for the module
     * @param DiInterface|null $dependencyInjector
     */
    public function registerServices(DiInterface $dependencyInjector = NULL)
    {

        $dependencyInjector->get('dispatcher')->setDefaultNamespace($this->appBase . '\\' . $this->appName . '\\Controllers');
        $dependencyInjector->get('view')->setViewsDir('../' . strtolower($this->appBase) . '/' . strtolower($this->appName) . '/views/');

    }
}