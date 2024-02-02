<?php
use Core\Handler\SocketIO;
use Core\Handler\User;
use Phalcon\Crypt;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Db\Adapter\Pdo\Postgresql;
use Phalcon\Http\Response\Cookies;

$DI->set('cookies', function () {
    $cookies = new Cookies();
    $cookies->useEncryption(false);
    return $cookies;
});

$DI->set('crypt',function () {
    $crypt = new Crypt();
    $crypt->setKey('"8`V>d^cf4`|6y>ls');
    return $crypt;
});

$DI->setShared('db', function () {
    try {


        $connect = new Postgresql(require_once(DIR_CONFIG.'postgre.php'));
    }
    catch (\Exception $e) {
        throw $e;
        die('not connect to postgre');
    }

    return $connect;
});

$DI->setShared('session', function () {
    $session = new Phalcon\Session\Manager();
    $files = new Phalcon\Session\Adapter\Stream();
    $session->setAdapter($files)->start();
    return $session;
});

/*$DI->setShared('redis', function () {
    $redis = new \Redis();
    $redis->connect('127.0.0.1', 6379);
    return $redis;
});*/

//$DI->setShared('socketIO',  new SocketIO());
$DI->setShared('user', new User());
/*
$DI->setShared('discordRPGI', function() use ($DI) {
    $discordRPGI  = new Core\Handler\Discord(require_once(DIR_CONFIG.'discord/rpginferno.php'));

    $discordRPGI->setDB($DI->getShared('db'));
    $discordRPGI->setUserId($DI->getShared('user')->getId());

    return $discordRPGI;
});

$DI->setShared('discordFreia', function() use ($DI) {
    $discordFreia = new Core\Handler\Discord(require_once(DIR_CONFIG.'discord/freia.php'));

    $discordFreia->setDB($DI->getShared('db'));
    $discordFreia->setUserId($DI->getShared('user')->getId());

    return $discordFreia;
});*/


