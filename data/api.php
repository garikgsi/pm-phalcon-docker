<?php
use Core\Controller\HttpApi;
use Core\Controller\HttpDomain;
use Core\Handler\MailSender;
use Core\Handler\Site;
use Phalcon\DI\FactoryDefault;
use Phalcon\Loader;
use Phalcon\Mvc\Micro;

require_once(DIR_CORE_CONTROLLER.'HttpApi.php');

try {
    $ApiHandler = new HttpApi();

    $ApiHandler->validateRequest();

    $namespacesList = [
        'Core\\Extender' =>  DIR_CORE.'extender',
        'Core\\Controller' =>  DIR_CORE.'controller',
        'Core\\Handler'  =>  DIR_CORE.'handler',
        'Core\\Driver'  =>  DIR_CORE.'driver',
        'Core\\Lib'      =>  DIR_CORE.'lib',
        'Core\\Model'    =>  DIR_CORE.'model',
        'Core\\Dict'     =>  DIR_CORE.'dict'
    ];

    $namespacesList[$ApiHandler->getModuleNamespace()] = $ApiHandler->getModuleDir();

    $Loader = new Loader();
    $Loader->registerNamespaces($namespacesList);

    require_once(DIR_CORE_VENDOR.'autoload.php');

    $Loader->register();

    $DomainHandler = new HttpDomain();
    $DomainHandler->validateDomain();

    $SiteHandler = new Site();
    $SiteHandler->configureSiteData($DomainHandler);

    $DI = new FactoryDefault();

    require_once(DIR_ROOT.'di.php');

    $DI->set('site', $SiteHandler);

    $App = new Micro();

    $App->setDI($DI);

    $ApiHandler->includeModule($App);

    $App->before(function() use (&$App, $ApiHandler) {
        $ApiHandler->initializeModule($App);
        return true;
    });

    $App->notFound(
        function () {
            die('not found 3');
        }
    );

    $request = new Phalcon\Http\Request();

    $App->handle($request->getURI());
}
catch (\Exception $e) {
    echo '<pre>';
    echo get_class($e), ": ", $e->getMessage(), "\n";
    echo " File=", $e->getFile(), "\n";
    echo " Line=", $e->getLine(), "\n";
    echo $e->getTraceAsString();
    echo '</pre>';
}