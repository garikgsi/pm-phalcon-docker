<?php
namespace Site\Front\Controllers;


use Core\Extender\ControllerApp;

/**
 * Class CounteragentsController
 * @package Site\Front\Controllers
 */
class OpfController extends ControllerApp
{
    public function initialize()
    {
        parent::initialize();

        $this->hapi->setHapiController('opf');
    }

    public function indexAction()
    {
        $this->view->setVar('mainMenuData', [
            'active' => 'pm_dict_opf',
            'overlay' => 1,
            'template' => 'partials/menu/menu_dicts',
            'icon' => 'address.svg',
            'title' => 'ОПФ',
        ]);

        $this->hapi->setHapiAction('index');

        $this->view->setVar('opfList', $this->db->query(
            '
                SELECT ol.opf_code
                     , ol.opf_type
                     , ol.opf_name_short
                     , ol.opf_name_full
                     , ol.counteragent_count
                FROM pm.opf_list AS ol
            '
        )->fetchAll());
    }
}