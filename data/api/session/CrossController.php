<?
namespace Core\Modules\User\Controller;

use Core\Lib\JSONResponse;
use Phalcon\Mvc\Controller;

/**
 * Class CrossController
 * Контроллер кросс-сайтовых авторизаций.
 *
 * @package Core\Modules\User\Controller
 * @property \Core\Lib\UserHandler user
 * @property \Phalcon\Db\Adapter\Pdo\Mysql db
 */
class CrossController extends Controller
{
    /**
     * Кросс-доменная авторизация по ключу, дергается через картинки обычным запросом
     * @param $key
     */
    public function crossAuthAction($key) {
        $db = $this->db;

        $row = $db->query('SELECT * FROM `scl_members_auth_hostnames` WHERE `auth_key`=:key', ['key' => $key])->fetch();

        if (!$row) {
            (new JSONResponse(JSONResponse::ERROR))->send();
        }

        $hostId   = $row['hostname_id'];
        $memberId = $row['member_id'];

        $db->query(
            'DELETE FROM `scl_members_auth_hostnames` WHERE `hostname_id`=:hid AND `member_id`=:mid',
            [
                'hid' => $hostId,
                'mid' => $memberId
            ]
        );

        if ($this->user->getId()) {
            (new JSONResponse(JSONResponse::ERROR))->send();
        }

        $this->user->authorization($memberId);

        (new JSONResponse(JSONResponse::SUCCESS))->send();
    }
}