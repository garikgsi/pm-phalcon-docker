<?php
use Phalcon\Di\FactoryDefault\Cli as CliDI;
use Phalcon\Cli\Console as ConsoleApp;
use Phalcon\Loader;
use Phalcon\Db\Adapter\Pdo\Postgresql;

define('DIR_ROOT',  __DIR__.'/');
define('DIR_CLI',  DIR_ROOT.'cli/');
define('DIR_PUBLIC',  DIR_ROOT.'public/');
define('DIR_CONFIG', DIR_ROOT.'config/');

// Использование стандартного CLI контейнера для сервисов
$DI = new CliDI();

/**
 * Регистрируем автозагрузчик и сообщаем ему директорию
 * для регистрации каталога задач
 */
$loader = new Loader();

$loader->registerDirs(
    [
        DIR_CLI . 'tasks/',
    ]
);

$loader->register();

$DI->setShared('db', function () {
    try {
        $connect = new Postgresql(require_once(DIR_CONFIG.'postgre.php'));
    }
    catch (\Exception $e) {
        die('not connect to postgre');
    }

    return $connect;
});

$DI->setShared('sdb', function () {
    try {
        $connect = new Postgresql(require_once(DIR_CONFIG.'postgre_sdn.php'));
    }
    catch (\Exception $e) {
        die('not connect to postgre');
    }

    return $connect;
});


// Создание консольного приложения
$console = new ConsoleApp();

$console->setDI($DI);

/**
 * Обработка аргументов консоли
 */
$arguments = [];

foreach ($argv as $key => $argument) {
    if ($key === 1) {
        $arguments['task'] = $argument;
    }
    else if ($key === 2) {
        $arguments['action'] = $argument;
    }
    else if ($key >= 3) {
        $arguments['params'][] = $argument;
    }
}

try {
    // Обработка входящих аргументов
    $console->handle($arguments);
} catch (\Phalcon\Exception $e) {
    // Связанные с Phalcon вещи указываем здесь
    // ..
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
} catch (\Throwable $throwable) {
    fwrite(STDERR, $throwable->getMessage() . PHP_EOL);
    exit(1);
} catch (\Exception $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}