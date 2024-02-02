<?php
namespace Core\Model;

use Phalcon\DI;

/**
 * @property \Phalcon\Db\Adapter\Pdo\Postgresql db
 */
class Settings
{
    protected $id = 0;
    protected $db;
    protected $settingsList = [];


    public static function initBySessionData($sessionData, $memberId) {
        return (new self())->fillDataFromSession($sessionData, $memberId);
    }

    public static function initByMemberId($memberId) {
        return (new self())->fillDataFromMemberId($memberId);
    }

    protected function __construct() {// Скрываем публичный конструктор, все идет только через статические методы
        $this->db = DI::getDefault()['db'];
    }

    public function fillDataFromMemberId($memberId) {
        $memberId = (int)$memberId;
        $this->id = $memberId;

        $settings = $this->db->query(
            '
            SELECT setting_id,
                   setting_name,
                   setting_config,
                   setting_value 
            FROM public.dp_members_get_settings(:mid)
            ',
            ['mid' => $memberId]
        );

        while($row = $settings->fetch()) {
            $config = json_decode($row['setting_config'], true);

            $this->settingsList[$row['setting_name']] = $this->parseValue(
                $config['type'],
                $config['default'],
                $row['setting_value']
            );
        }

        return $this;
    }

    public function fillDataFromSession($sessionData, $memberId) {
        $this->settingsList = $sessionData;
        $this->id = $memberId;

        return $this;
    }

    public function refreshData($memberId) {
        $this->id = $memberId;
        $this->fillDataFromMemberId($this->id);
    }

    public function getDataToStore() {
        return $this->settingsList;
    }

    public function getSetting($settingName) {
        return $this->settingsList[$settingName] ?? '';
    }

    public function changeSetting($settingName, $settingValue) {
        $this->settingsList[$settingName] = $settingValue;
    }

    private function parseValue($type, $default, $value) {
        $value = is_null($value) ? $default : $value;

        switch ($type) {
            case 'integer':
                $value = (int)$value;
                break;
            case 'bool':
                $value = (int)$value ? 1 : 0;
                break;
            case 'float':
                $value = (float)$value;
                break;
            case 'array':
                $array = !is_array($value) ? json_decode($value, true) : $value;
                $value = !is_array($array) ? $default : $array;

                for ($a = 0, $len = sizeof($value); $a < $len; $a++) {
                    $value[$a] = (int)$value[$a];
                }

                break;
            case 'string':
                $value = preg_replace('/[^a-zA-Z0-9_-]/ui', '', $value);
                break;
        }

        return $value;
    }

}