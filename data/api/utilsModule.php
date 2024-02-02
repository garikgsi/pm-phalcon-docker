<?php
use Phalcon\Mvc\Micro\Collection as MicroCollection;

return function(Phalcon\Mvc\Micro &$App) {
    $moduleHandler = 'Api\Utils\\';

    $routesTemplates = new MicroCollection();
    $routesTemplates->setHandler($moduleHandler.'TemplatesController', true);
    $routesTemplates->setPrefix('/api/utils/templates/');

    $routesTemplates->post('package/{package:[0-9a-zA-Z\-_/]+}', 'package');

    $App->mount($routesTemplates);



    $routesSystem = new MicroCollection();
    $routesSystem->setHandler($moduleHandler.'SystemController', true);
    $routesSystem->setPrefix('/api/utils/system/');

    $routesSystem->get('configs', 'configs');

    $App->mount($routesSystem);



    $routesJs = new MicroCollection();
    $routesJs->setHandler($moduleHandler.'JsController', true);
    $routesJs->setPrefix('/api/utils/js/');

    $routesJs->get('lang', 'lang');
    $routesJs->get('package', 'package');

    $App->mount($routesJs);



    $routesImport = new MicroCollection();
    $routesImport->setHandler($moduleHandler.'ImportController', true);
    $routesImport->setPrefix('/api/utils/import/');

    $routesImport->get('members', 'importMembers');

    $App->mount($routesImport);
};