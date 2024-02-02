<?php
namespace Core\Extender;

/**
 * Оболочка  для контроллеров системы Micro, добавляющая ограничения в правах уровня Басик - гости, не авторизованные
 * и забаннеые не будут иметь доступ к методам.
 *
 * Инициализация родителя производится ДО проверки прав, это нужно чтобы сначала проверить факт АЯКСа, может до юзера
 * дело и не дойдет.
 *
 * Class ApiAccessAjaxBasic
 * @package Core\Extender
 * @property \Core\Handler\User user
 */
class ApiAccessAjaxBasic extends ApiAccessAjax
{
    public function initialize() {
        parent::initialize();

        if (!$this->user->hasBasicRights()) {
            $this->sendResponseByCode(401);
        }
    }
}


