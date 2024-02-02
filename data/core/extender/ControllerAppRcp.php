<?php
namespace Core\Extender;

/**
 * Оболочка для автоматической проверки авторизации в RCP.
 *
 * Class ControllerAppBasic
 * @package Core\Extender
 * @property \Core\Handler\User user
 */
class ControllerAppRcp extends ControllerApp
{
    public function initialize() {
        parent::initialize();

        if (!$this->user->rcpLogged()) {
            $this->response->redirect('?'.base64_encode ($_SERVER['REQUEST_URI']));
            $this->view->disable();

            return;
        }


        $this->view->setTemplateAfter('rcp_wrap');
    }
}