<?php
namespace Core\Extender;

/**
 *
 *
 * Class ControllerAppBasic
 * @package Core\Extender
 * @property \Core\Handler\User user
 */
class ControllerAppPmLogged extends ControllerApp
{
    public function initialize() {
        parent::initialize();

        if (!$this->user->hasBasicRights()) {
            $this->response->redirect('?'.base64_encode ($_SERVER['REQUEST_URI']));
            $this->view->disable();

            return;
        }


        $this->view->setMainView('render_levels/main/index');
    }
}