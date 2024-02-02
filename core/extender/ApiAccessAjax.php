<?php
namespace Core\Extender;

/**
 * Оболочка для контроллеров системы Micro, которые запрещают обращения не через AJAX
 *
 * Class ApiAccessAjax
 * @package Core\Extender
 */
class ApiAccessAjax extends ControllerSender
{
    public function initialize()
    {
        if (!$this->request->isAjax() && !sizeof($_FILES)) {
            $this->sendResponseByCode(404);
        }
    }
}