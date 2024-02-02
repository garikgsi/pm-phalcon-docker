<?php
namespace Site\Front\Controllers;


use Core\Extender\ControllerApp;

/**
 * Class CounteragentsController
 * @package Site\Front\Controllers
 */
class OrdersController extends ControllerApp
{
    public function initialize()
    {
        parent::initialize();

        $this->hapi->setHapiController('orders');
    }

    public function indexAction()
    {

    }

    protected function apiNotification_list()
    {
        $this->sendResponseAjax([
            'state' => 'yes',
            'contracts' => $this->db->query(
                '
                SELECT ot.order_notification_id
                     , (ot.notification_month || \' месяц\') AS notification_title
                FROM pm.order_notification AS ot
                ORDER BY ot.notification_month 
            '
            )->fetchAll()
        ]);
    }


}