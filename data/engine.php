<?php

use Core\Builder\Html;
use Core\Controller\HttpDomain;
use Core\Engine\Mustache;
use Core\Extender\ViewScopeFix;
use Core\Handler\HistoryApi;
use Core\Handler\OpenGraph;
use Core\Handler\Site;
use Core\Handler\TwitterCards;
use Core\Lib\BreadCrumbs;
use Phalcon\DI\FactoryDefault;
use Phalcon\Loader;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Router;
use Phalcon\Url;
use Phalcon\Mvc\View;
use voku\helper\HtmlMin;

try {
    Model::setup(
        [
            'notNullValidations' => false
        ]
    );


    $Loader = new Loader();

    require_once(DIR_CORE.'vendor/autoload.php');

    $Loader->registerNamespaces([
        'Core\\Builder'  =>  DIR_CORE.'builder',
        'Core\\Controller' => DIR_CORE.'controller',
        'Core\\Engine'   =>  DIR_CORE.'engine',
        'Core\\Driver'   =>  DIR_CORE.'driver',
        'Core\\Extender' =>  DIR_CORE.'extender',
        'Core\\Handler'  =>  DIR_CORE.'handler',
        'Core\\Lib'      =>  DIR_CORE.'lib',
        'Core\\Model'    =>  DIR_CORE.'model',
        'Core\\Dict'     =>  DIR_CORE.'dict',
        'Freia\\Controller' =>  DIR_ROOT.'freia/controller',
        'Freia\\Model'      =>  DIR_ROOT.'freia/model'
    ]);

    $Loader->register();

    $DomainHandler = new HttpDomain();
    $DomainHandler->validateDomain();

    $SiteHandler = new Site();
    $SiteHandler->configureSiteData($DomainHandler);

    $DI = new FactoryDefault();

    require_once(DIR_ROOT.'di.php');

    $DI->set(
        'url',
        function ()  {
            $url = new Url();

            $url->setBaseUri('//'.strtolower($_SERVER['HTTP_HOST']).'/');

            return $url;
        }
    );

    $DI->setShared('hapi', new HistoryApi(mb_strtolower($SiteHandler->getModuleName())));
    $DI->setShared('site', $SiteHandler);
    $DI->setShared('twitterCards', new TwitterCards($SiteHandler));
    $DI->setShared('openGraph', new OpenGraph($SiteHandler));
    $DI->setShared('html', new Html());
    $DI->setShared('daData', function ()  {
        $token = '9ab73dcbf33f7684de61e4494ed9e8c69d2c3be0';
        $secret = 'cb75bedf154915121ad9b01f74559b3b271df3fd';

        return new \Dadata\DadataClient($token, $secret);
    });


    $DI->setShared('breadcrumbs', function () {
        $breadCrumbs = new BreadCrumbs();

        return $breadCrumbs;
    });

    $DI->set(
        'router',
        function () use ($SiteHandler) {
            $router = new Router($SiteHandler->isDefaultRouterEnabled());

            $router->removeExtraSlashes(true);
            $router->setDefaultModule($SiteHandler->getModuleName());
            /*$router->setDefaultController('index');
            $router->setDefaultAction('index');*/

            $router->add('/', [
                'controller' => 'index',
                'action'     => 'index'
            ]);

            $router->add('/:controller/api/:params', [
                'controller' => 1,
                'action'     => 'api',
                'params'     => 2
            ]);

            $router->add('/:controller/flu/:params', [
                'controller' => 1,
                'action'     => 'flu',
                'params'     => 2
            ]);

            $siteRoutes = $SiteHandler->getRoutes();

            for($a = 0, $len = sizeof($siteRoutes); $a < $len; $a++) {
                $route = $siteRoutes[$a];

                $router->add($route['patterns'], $route['paths']);
            }

            $router->notFound([
                'controller' => 'index',
                'action'     => 'route404',
            ]);

            return $router;
        }
    );

    $DI->set(
        'dispatcher',
        function() use ($DI) {
            $eventsManager = $DI->getShared('eventsManager');

            $eventsManager->attach(
                'dispatch:beforeException',
                function($event, $dispatcher, $exception) {
                    switch ($exception->getCode()) {
                        case Phalcon\Dispatcher\Exception::EXCEPTION_HANDLER_NOT_FOUND:
                        case Phalcon\Dispatcher\Exception::EXCEPTION_ACTION_NOT_FOUND:
                            $dispatcher->forward([
                                'controller' => 'index',
                                'action'     => 'route404',
                            ]);
                            return false;
                            break; // for checkstyle
                        /*default:
                            die('c404_2');
                            $dispatcher->forward(
                                array(
                                    'controller' => 'error',
                                    'action' => 'uncaughtException',
                                )
                            );
                            return false;
                            break; // for checkstyle*/
                    }
                }
            );

            $dispatcher = new Dispatcher();
            $dispatcher->setEventsManager($eventsManager);

            return $dispatcher;
        },
        true
    );

    $DI->set(
        'view',
        function () use ($DI, $SiteHandler) {
            //$View = new View();

            $View = new ViewScopeFix();

            $View->setViewsDir($SiteHandler->getDir().'views/');
            $View->setLayoutsDir('render_levels/layout/');
            $View->setMainView('render_levels/main/index');

            $EventManager = new \Phalcon\Events\Manager();

            $View->setEventsManager($EventManager);

            //$View->setLayout('wrapper');

            $Engine = new Mustache($View, $DI);

            $NativeEngine = new Phalcon\Mvc\View\Engine\Php($View, $DI);

            $View->registerEngines([
                '.hbs'   => $Engine,
                '.phtml' => $NativeEngine
            ]);

            return $View;
        }
    );

    $App = new Application($DI);

    $App->registerModules($SiteHandler->getModuleRegistrationData());

    $request = new Phalcon\Http\Request();
    $html = $App->handle($request->getURI())->getContent();

    if ($SiteHandler->isMinificationEnabled() && $DI['user']->isDevCompress()) {
        $HtmlMin = new HtmlMin();

        $HtmlMin->doRemoveSpacesBetweenTags(true);
        $HtmlMin->doRemoveOmittedQuotes(false);
        $HtmlMin->doRemoveOmittedHtmlTags(false);

        $html = $HtmlMin->minify($html);
    }

    if ($DI['request']->isAjax()) {
        $DI['hapi']->sendHapiResponse($html);
    }
    else {
        echo $html;
    }
}
catch (\Exception $e) {
    echo '<pre>';
    echo get_class($e), ": ", $e->getMessage(), "\n";
    echo " File=", $e->getFile(), "\n";
    echo " Line=", $e->getLine(), "\n";
    echo $e->getTraceAsString();
    echo '</pre>';
}