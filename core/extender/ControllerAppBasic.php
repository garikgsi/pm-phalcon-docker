<?php
namespace Core\Extender;

/**
 * Оболочка для автоматической проверки базового набора прав.
 * Базовый набор прав есть только у активированных и не забаненных пользователей.
 * Только после проверки выполняются инициализаторы родителей.
 *
 * Class ControllerAppBasic
 * @package Core\Extender
 * @property \Core\Handler\User user
 */
class ControllerAppBasic extends ControllerApp
{
    public function initialize() {
        if (!$this->user->hasBasicRights()) {
            $this->sendResponseByCode(401);
        }

        parent::initialize();
    }
}