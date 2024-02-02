<?php
namespace Core\Controller;

use Core\Driver\MemberSql;
use Core\Lib\PasswordHash;
use Core\Model\Member;
use Core\Model\Settings;
use Phalcon\Di\Injectable;

/**
 *
 * @property \Phalcon\Db\Adapter\Pdo\Postgresql db
 * @property \Phalcon\Session\Adapter\Files session
 * @property \Phalcon\Http\Response\Cookies cookies
 */
class UserSession extends Injectable
{
    private static $instance;

    private $dataRefreshInterval;

    private $sessionRefreshName;
    private $sessionContainerName;
    private $sessionContainerSett;
    private $sessionLoggedName;
    private $sessionRCPLoggedName;

    public $cookieAuthorizationKey;
    private $cookieExpirationTime;

    public function __construct() {
        if (self::$instance) {
            return self::$instance;
        }

        $config = require_once(DIR_CONFIG.'user_session.php');

        $configSession = $config['session'];
        $configCookie = $config['cookie'];

        $this->dataRefreshInterval = $config['refresh_time'];

        $this->sessionRefreshName = $configSession['last_time_refreshed'];
        $this->sessionContainerName = $configSession['data_container'];
        $this->sessionContainerSett = $configSession['sett_container'];
        $this->sessionLoggedName = $configSession['logged_flag'];
        $this->sessionRCPLoggedName = $configSession['rcp_logged_flag'];

        $this->cookieAuthorizationKey = $configCookie['key'];
        $this->cookieExpirationTime = $configCookie['expiration'];

        self::$instance = $this;

        return $this;
    }

    public function refreshStoredData($userData) {
        $this->session->set($this->sessionRefreshName, date('U'));
        $this->storeData($userData);
    }

    public function storeData($userData) {
        $this->session->set($this->sessionContainerName, json_encode($userData));
        $this->session->set($this->sessionLoggedName, 1);
    }

    public function storeSett($settData) {
        $this->session->set($this->sessionContainerSett, json_encode($settData));
    }

    public function isTimeToRefresh() {
        $refreshInterval = $this->dataRefreshInterval;

        if ($refreshInterval <= 0) {
            return false;
        }

        $currentUnixTime = date('U');
        $lastRefreshUnixTime =(int)$this->session->get($this->sessionRefreshName);

        return $currentUnixTime - $lastRefreshUnixTime >= $refreshInterval;
    }

    /**
     * @return Member
     */
    public function getStoredUserData() {
        $isSessionLogged = (int)$this->session->get($this->sessionLoggedName);


        if ($isSessionLogged) {
            return Member::initBySessionData(json_decode($this->session->get($this->sessionContainerName), true));
        }

        return Member::initByCookieKey($this->cookies->get($this->cookieAuthorizationKey));
    }

    /**
     * @return Settings
     */
    public function getStoredSettingsData($memberId = 0) {
        if (!$this->session->get($this->sessionContainerSett)) {
            $settings = Settings::initByMemberId($memberId);

            $this->storeSett($settings->getDataToStore());
        }
        else {
            $settings = Settings::initBySessionData(json_decode($this->session->get($this->sessionContainerSett), true), $memberId);
        }

        return $settings;
    }

    public function authorizeSettingsModel($memberId) {
        $settingModel = Settings::initByMemberId($memberId);

        $this->storeSett($settingModel->getDataToStore());
        return $settingModel;
    }

    /**
     * @param $pass
     * @param $email
     * @return bool|Member
     */
    public function authorizeUser($pass, $email) {
        $memberId = PasswordHash::tryToLogIn($pass, $email);

        if (!$memberId) {
            return false;
        }

        return $this->prepareAuthorizedUser($memberId);
    }

    /**
     * @param $memberId
     * @return Member
     */
    public function authorizeUserById($memberId) {
        return $this->prepareAuthorizedUser($memberId);
    }

    /**
     * @param $memberId
     * @return Member
     */
    private function prepareAuthorizedUser($memberId) {
        $cookieKey = PasswordHash::passGenHash(
            PasswordHash::passGenSalt(),
            PasswordHash::passGenSalt(),
            PasswordHash::passGenSalt()
        );

        $this->cookies->set($this->cookieAuthorizationKey, $cookieKey, $this->cookieExpirationTime);

        $memberModel = Member::initByMemberId($memberId);

        $memberModel->addCookie($cookieKey);

        $this->refreshStoredData($memberModel->getDataToStore());

        return $memberModel;
    }

    public function logout() {
        MemberSql::dbCookieDelete($this->cookies->get($this->cookieAuthorizationKey));

        $this->session->remove($this->sessionRCPLoggedName);
        $this->session->remove($this->sessionContainerName);
        $this->session->remove($this->sessionContainerSett);
        $this->session->remove($this->sessionLoggedName);
        $this->session->remove($this->sessionRefreshName);

        $this->cookies->delete($this->cookieAuthorizationKey);
    }

    public function isRCPLogged() {
        return (int)$this->session->get($this->sessionRCPLoggedName) != 0 ? 1 : 0;
    }

    public function setRCPLogged() {
        $this->session->set($this->sessionRCPLoggedName, 1);
    }
}