<?php
use Phalcon\Mvc\Micro\Collection as MicroCollection;

return function(Phalcon\Mvc\Micro &$App) {
    $moduleHandler = 'Api\App\\';

    $routesSettings = new MicroCollection();
    $routesSettings->setHandler($moduleHandler.'SettingsController', true);
    $routesSettings->setPrefix('/api/app/settings/');

    $routesSettings->post('getFullData', 'getFullData');
    $routesSettings->post('getSocial',   'getSocial');

    $routesSettings->post('setSetting',  'setSetting' );

    $routesSettings->post('setLang', 'setLang');
    $routesSettings->post('setNick', 'setNick');
    $routesSettings->post('setPass', 'setPass');
    $routesSettings->post('setMail', 'setMail');

    $routesSettings->post('uploadAvatar', 'uploadAvatar');
    $routesSettings->post('deleteAvatar', 'deleteAvatar');
    $routesSettings->post('saveAvatar', 'saveAvatar');


    $App->mount($routesSettings);
};