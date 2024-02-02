<?php
namespace Core\Extender;

use Phalcon\Mvc\Controller;

/**
 * Оболочка для добавления стандартизованных ответов с завершением выполнения скрипта:
 * 1. Функция отправки ответа в виде Жисон
 * 2. Функция отправки статусного заголовка
 *
 *
 * Class ControllerSender
 * @package Core\Extender
 * @property \Core\Handler\User user
 * @property \Phalcon\Db\Adapter\Pdo\Postgresql db
 * @property \Phalcon\Db\Adapter\Pdo\Postgresql kdb
 */
class ControllerSender extends Controller
{
    /**
     * Функция формирования и отправки JSON ответа на Аякс запрос
     *
     * @param mixed $data - любые данные для отправки, которыме могут быть перведены в Жисон
     * @param int $status - статус: 0 - провал, 1 - успех
     */
    public function sendResponseAjax($data = [], $status = 1) {
        $statuses = [
            'error',
            'success'
        ];

        $this->response->setJsonContent(['status' => $statuses[$status], 'data' => $data]);
        $this->response->send();
        exit;
    }
    /**
     * Функция формирования и отправки JSON ответа на Аякс запрос
     *
     * @param mixed $data - любые данные для отправки, которыме могут быть перведены в Жисон
     * @param int $status - статус: 0 - провал, 1 - успех
     */
    public function sendResponseAjaxGZIP($data = [], $status = 1) {
        $statuses = [
            'error',
            'success'
        ];

        $this->response->setContent(
            base64_encode(gzcompress(json_encode(['status' => $statuses[$status], 'data' => $data]),9))
        );
        $this->response->send();
        exit;
    }

    /**
     * Функция отправки статусного сообщения
     *
     * @param integer $code
     */
    public function sendResponseByCode($code) {
        $codes = [
            401 => 'Unauthorized',
            404 => 'Not Found',
            500 => 'Internal Server Error'
        ];

        $this->response->setStatusCode($code, $codes[$code])->sendHeaders();
        exit;
    }

    /**
     * Функция для переадресации с отключением рендерера
     *
     * @param string $uri - URI для переадресации
     */
    public function redirect($uri) {
        $this->response->redirect(/*$this->url->get(*/$uri/*)*/);
        $this->view->disable();
    }
}