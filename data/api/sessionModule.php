<?php
use Phalcon\Mvc\Micro\Collection as MicroCollection;

return function(Phalcon\Mvc\Micro &$App) {
    $moduleHandler = 'Api\Session\\';

    // РЕГИСТРАЦИОННЫЕ ОПЕРАЦИИ
    //------------------------------------------------------------------------------------------------------------------
    $routesRegistration = new MicroCollection();
    $routesRegistration->setHandler($moduleHandler.'RegistrationController', true);
    $routesRegistration->setPrefix('/api/session/registration/');

    $routesRegistration->post('checkEmail', 'checkEmail');
    $routesRegistration->post('checkNick',  'checkNick');
    $routesRegistration->post('register',   'register');
    $routesRegistration->post('discord',   'discord');


    $App->mount($routesRegistration);

    // АКТИВАЦИОННЫЕ ОПЕРАЦИИ
    //------------------------------------------------------------------------------------------------------------------
    $routesActivation = new MicroCollection();
    $routesActivation->setHandler($moduleHandler.'ActivationController', true);
    $routesActivation->setPrefix('/api/session/activation/');

    $routesActivation->post('activate',     'activate');
    $routesActivation->post('resend',       'resend');
    $routesActivation->post('widget',       'widget');

    // проверка существования ключа для смены неактивированного имейла
    $routesActivation->post('activateMailCheck', 'activateMailCheck');
    $routesActivation->post('activateMail', 'activateMail');

    $routesActivation->post('checkEmail',       'checkEmail');
    $routesActivation->post('checkEmailResend', 'checkEmailResend');

    $routesActivation->post('confirmMailChange', 'confirmMailChange');

    $App->mount($routesActivation);

    // ВОССТАНОВЛЕНИЕ ПАРОЛЯ
    //------------------------------------------------------------------------------------------------------------------
    $routesReminder = new MicroCollection();
    $routesReminder->setHandler($moduleHandler.'ReminderController', true);
    $routesReminder->setPrefix('/api/session/reminder/');

    $routesReminder->post('send',       'send');
    $routesReminder->post('change',     'change');
    $routesReminder->post('checkCode',  'checkCode');
    $routesReminder->post('checkEmail', 'checkEmail');


    $App->mount($routesReminder);

    // АВТОРИЗАЦИОННЫЕ ОПЕРАЦИИ
    //------------------------------------------------------------------------------------------------------------------
    $routesSession = new MicroCollection();
    $routesSession->setHandler($moduleHandler.'SessionController', true);
    $routesSession->setPrefix('/api/session/session/');

    $routesSession->post('login',  'login');
    $routesSession->post('logout', 'logout');


    $App->mount($routesSession);

    // ПОЛУЧЕНИЕ ПОЛЬЗОВАТЕЛЬСКОГО МЕНЮ
    //------------------------------------------------------------------------------------------------------------------
    $routesMenu = new MicroCollection();
    $routesMenu->setHandler($moduleHandler.'MenuController', true);
    $routesMenu->setPrefix('/api/session/menu/');

    $routesMenu->post('getRoot', 'getRoot');


    $App->mount($routesMenu);

};