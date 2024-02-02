<?php

use Phalcon\Cli\Task;

class MainTask extends Task
{
    public function mainAction()
    {
        echo 'Это задача по умолчанию и действие по умолчанию' . PHP_EOL;
    }

    /**
     * @param $params
     * @cmd php cli.php main test
     */
    public function testAction($params, $cccc, $ffff)
    {
        echo sprintf('hello %s', $params);

        echo PHP_EOL;

        echo sprintf('best regards, %s', $ffff);

        echo PHP_EOL;
    }
}