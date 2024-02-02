<?php
namespace Core\Extender;

/**
 * Оболочка  для контроллеров системы Micro, добавляющая ограничения в правах уровня разработчик
 *
 *
 * Class ApiAccessAjaxBasic
 * @package Core\Extender
 * @property \Core\Handler\User user
 */
class ApiAccessAjaxDeveloper extends ApiAccessAjaxBasic
{
    public function initialize() {
        parent::initialize();

        if (!$this->user->isDeveloper()) {
            $this->sendResponseByCode(401);
        }
    }
}


