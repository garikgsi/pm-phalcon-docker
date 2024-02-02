<?php
namespace Core\Handler;

use Phalcon\Di\Injectable;

/**
 * Class SocketIO
 * @package Core\Handler
 *
 * @property \Redis redis
 */
class SocketIO extends Injectable
{
    private static $instance = null;

    private $sessionName = '';

    private $algorithmTypes = [
        'auth' => 'crc32b',
        'join' => 'adler32'
    ];

    const ALGO_AUTH = 'auth';
    const ALGO_JOIN = 'join';
    const REDIS_TRANSPORT_SUBSCRIBE = 'php-socket-ic-transport-protocol';

    public function __construct() {
        if (self::$instance) {
            return self::$instance;
        }

        self::$instance = $this;

        return $this;
    }


    public function getSessionName() {
        return $this->sessionName;
    }

    /**
     * Генерация одноразового ключа для подключения к Сокету
     *
     * @param  array $memberRow
     * @return int|string
     */
    public function createAuthKey($memberRow)
    {
        if ($this->request->isAjax()) {
            return 0;
        }

        return $this->sessionName = $this->createRedisKey($memberRow, self::ALGO_AUTH);
    }

    /**
     * Генерация одноразового ключа для подключения к указанной комнате
     *
     * @param  array $joinData
     * @return int|string
     */
    public function createJoinKey($joinData) {
        return $this->createRedisKey($joinData, self::ALGO_JOIN);
    }


    /**
     *
     *
     * @param string $room
     * @param string $event
     * @param array  $data
     */
    public function sendMessageToNodeJS($room, $event, $data) {
        $this->redis->publish(self::REDIS_TRANSPORT_SUBSCRIBE, json_encode([
            'room'  => $room,
            'event' => $event,
            'data'  => $data
        ]));
    }

    /**
     * Генерация и запись ключа доступа в REDIS
     *
     * @param array $data
     * @param string $type
     * @return string
     */
    private function createRedisKey($data, $type) {
        $json  = json_encode($data);
        $redis = $this->redis;

        $key = $this->hashKey($json, $this->algorithmTypes[$type]);

        $redis->set($key, $json);
        $redis->expireAt($key, date('U') + 20);

        return $key;
    }

    /**
     * Создание одноразового хеш ключа
     *
     * @param string $json
     * @param string $algorithm
     * @return string
     */
    private function hashKey($json, $algorithm) {
        return hash(
            $algorithm,
            mt_rand(0,19).mt_rand(0,19).$json.mt_rand(0,19).mt_rand(0,19),
            false
        );
    }
}