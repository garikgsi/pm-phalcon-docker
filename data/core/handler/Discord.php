<?php
namespace Core\Handler;

use Core\Handler\Discord\OAuth2;
use Core\Handler\Discord\RequestToken;
use Core\Handler\Discord\RequestUser;

require_once('discord/OAuth2.php');
require_once('discord/Request.php');
require_once('discord/RequestToken.php');
require_once('discord/RequestUser.php');


/**
 *
 * @property \Phalcon\Db\Adapter\Pdo\Postgresql db
 */
class Discord
{
    private $config;

    private $OAuth2;
    private $RequestToken;
    private $RequestUser;

    private $userId;
    private $db;

    public function __construct($config) {
        $this->config = $config;

        $this->OAuth2  = new OAuth2($config);
        $this->RequestToken = new RequestToken($config);
        $this->RequestUser  = new RequestUser($config);
    }

    public function setUserId($userId) {
        $this->userId = (int)$userId;
    }

    public function setDB($db) {
        $this->db = $db;
    }

    public function setAuthorizeCode($code) {
        $this->RequestToken->setCode($code);
    }

    public function authorizeRedirect() {
        $this->OAuth2->authorizeRedirect();
    }

    public function requestAccessToken()
    {
        // Запрашиваем токен пользователя
        $result = $this->RequestToken->requestAccessToken();

        $status = [
            'action' => '',
            'member' => $this->userId
        ];

        $accessToken = $result['access_token'];
        $refreshToken = $result['refresh_token'];
        $tokenType = $result['token_type'];
        $scope = $result['scope'];
        $expiresIn = $result['expires_in'];
        $updatedAt = date('U');

        if (!isset($result['access_token'])) {
            $status['action'] = 'error';

            return $status;
        }

        // Запрашиваем пользователя дискорд
        $this->RequestUser->setAccessToken($accessToken);

        $userInfo = $this->RequestUser->getMe();

        if ($userInfo['verified'] == 0) {
            $status['action'] = 'error_verification';

            $status['discord'] = [
                'id' => $userInfo['id'],
                'name' => $userInfo['username'],
                'disc' => $userInfo['discriminator'],
                'avatar' => $userInfo['avatar'],
            ];

            return $status;
        }

        // Добавляем или обновляем дискорд пользователя
        $discordUserId = $this->db->query(
            '
                SELECT public.dp_discord_user_update(
                    :sid::integer,
                    :did::varchar(32),
                    :name::varchar(128),
                    :disc::varchar(4),
                    :locale::varchar(10),
                    :verified::bool,
                    :email::varchar(256),
                    :flags::integer,
                    :public_flags::integer,
                    :avatar::varchar(32)                
                ) AS discord_user_id
            ',
            [
                'sid' => $this->config['source_id'],
                'did' => $userInfo['id'],
                'name' => mb_substr($userInfo['username'], 0, 128),
                'disc' => $userInfo['discriminator'],
                'locale' => $userInfo['locale'],
                'verified' => $userInfo['verified'],
                'email' => $userInfo['email'],
                'flags' => $userInfo['flags'],
                'public_flags' => $userInfo['public_flags'],
                'avatar' => $userInfo['avatar']
            ]
        )->fetch()['discord_user_id'];

        $memberId = (int)$this->db->query(
            '
                SELECT public.dp_discord_user_oauth2_update(
                    :oid::integer,
                    :uid::bigint,
                    :token_access::varchar(32),
                    :token_refresh::varchar(32),
                    :token_type::varchar(12),
                    :scope::varchar(128),
                    :expires::integer,
                    :updated::integer,
                    :code::varchar(32)
                ) AS member_id
            ',
            [
                'oid' => $this->config['oauth2_id'],
                'uid' => $discordUserId,
                'token_access' => $accessToken,
                'token_refresh' => $refreshToken,
                'token_type' => $tokenType,
                'scope' => $scope,
                'expires' => $expiresIn,
                'updated' => $updatedAt,
                'code' => $this->RequestToken->getCode()
            ]
        )->fetch()['member_id'];



        if ($memberId > 0) {
            $status['action'] = 'login';
            $status['member'] = $memberId;
        }
        else {
            if ($this->userId) {
                $this->db->query(
                    'SELECT public.dp_members_discord_add(:uid::bigint, :mid::bigint)',
                    [
                        'uid' => $discordUserId,
                        'mid' => $this->userId
                    ]
                );

                $status['action'] = 'login';
            }
            else {
                // Пытаемся найти пользователя по имейлу: -1 пользователь связан, 0 пользователя нет, остальное user id
                $compareCode = (int)$this->db->query(
                    'SELECT public.dp_members_discord_compare(:email::varchar(100)) AS member_id',
                    ['email' => $userInfo['email']]
                )->fetch()['member_id'];

                if ($compareCode > 0) {
                    $status['action'] = 'login';
                    $status['member'] = $compareCode;
                }
                else if($compareCode == 0) {
                    $status['action'] = 'registration'; // нужна регистрация потому что пользака нет
                }
                else if($compareCode < 0) {
                    $status['action'] = 'error_mail'; // нужна регистрация, тк имейл занят
                }
            }
        }


        $status['discord'] = [
            'id' => $userInfo['id'],
            'name' => $userInfo['username'],
            'disc' => $userInfo['discriminator'],
            'avatar' => $userInfo['avatar'],
        ];

        return $status;
    }
}
