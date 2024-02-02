<?php
namespace Core\Handler;

use Core\Dict\MemberGroups;
use Core\Handler\User\UserAccess;
use Core\Lib\PasswordHash;
use Core\Model\Member;
use Core\Model\Settings;
use Phalcon\Di\Injectable;

/**
 * @property \Phalcon\Db\Adapter\Pdo\Postgresql db
 * @property \Core\Handler\SocketIO socketIO
 * @property \Core\Handler\Site site
 * @property \Core\Controller\UserSession userSession
 * @property \Core\Model\Member userModel
 * @property \Core\Model\Settings userSettings
 */
class User extends Injectable
{
    private static $instance = null;

    const SESSION_RCP    = 'fr_user_rcp';

    private $device = 'desktop';

    private $userSession;
    private $userModel;
    private $userSettings;

    private $UserAccess;



    public function __construct() {
        if (self::$instance) {
            return self::$instance;
        }

        $this->userSession = new \Core\Controller\UserSession();
        $this->userModel = $this->userSession->getStoredUserData();
        $this->userSettings = $this->userSession->getStoredSettingsData($this->userModel->getId());

        $this->initializeUserData();

        return self::$instance = $this;
    }

    private function initializeUserData() {
        if ((new \Mobile_Detect())->isMobile()) {
            $this->device = 'mobile';
        }

        if ($this->hasBasicRights()) {
            //$this->socketIO->createAuthKey(['member_id' => $this->getId()]);
        }

        if ($this->userSession->isTimeToRefresh()) {
            $this->refresh();
        }


        $this->userSession->storeData($this->userModel->getDataToStore());
        $this->userSession->storeSett($this->userSettings->getDataToStore());
    }


    public function refresh() {
        /*if (!$this->getId()) {
            return;
        }*/

        $this->userModel->refreshData($this->userSession->cookies->get($this->userSession->cookieAuthorizationKey));
        $this->userSettings->refreshData($this->getId());
        $this->userSession->refreshStoredData($this->userModel->getDataToStore());
        $this->userSession->storeSett($this->userSettings->getDataToStore());
    }

    public function logout() {
        $this->userModel = Member::initByMemberId(0);
        $this->userSettings = Settings::initByMemberId(0);
        $this->userSession->logout();
    }

    public function getDevice() {
        return $this->device;
    }

    public function getId() {
        return $this->userModel->getId();
    }

    public function getNick() {
        return $this->userModel->getNick();
    }

    public function getGroup() {
        return $this->userModel->getGroup();
    }

    public function getAge() {
        return $this->userModel->getAge();
    }

    public function getGender() {
        return $this->userModel->getGender();
    }

    public function getEmail() {
        return $this->userModel->getEmail();
    }

    public function getAvatar() {
        return $this->userModel->getAvatar();
    }

    public function getAvatarType() {
        return $this->userModel->getAvatarType();
    }

    public function getLang() {
        return $this->site->getLangName();
    }

    public function getLangId() {
        return $this->site->getLangId();
    }

    public function getDataToStore() {
        return $this->userModel->getDataToStore();
    }

    public function getSetting($settingName) {
        return $this->userSettings->getSetting($settingName);
    }

    public function changeSetting($settingName, $settingValue) {
        $this->userSettings->changeSetting($settingName, $settingValue);
        $this->userSession->storeSett($this->userSettings->getDataToStore());
    }

    public function isLogged() {
        return $this->getId() > 0;
    }

    public function isActive() {
        return $this->isLogged() && !isset(MemberGroups::NOT_ACTIVATED[$this->getGroup()]);
    }

    public function isDeveloper() {
        return [1608 => 1, 1 => 1][$this->getId()] ?? 0;//$this->getGroup() == MemberGroups::ADMINISTRATOR;
    }

    public function isDevCompress() {
        return !(!DEV_COMPRESS && $this->isDeveloper());
    }

    public function hasBasicRights() {
        return $this->isLogged() && $this->isActive() && !isset(MemberGroups::BANNED_GROUPS[$this->getGroup()]);
    }

    public function rcpLogged() {
        return $this->userSession->isRCPLogged();
    }

    public function loginById($memberId) {
        $this->userModel = $this->userSession->authorizeUserById($memberId);
        $this->userSettings = $this->userSession->authorizeSettingsModel($this->userModel->getId());
    }

    public function loginAsUser($pass, $email, $memberId = 0) {
        $result = $this->userSession->authorizeUser($pass, $email);

        if (!$result) {
            return false;
        }

        $this->userModel = $result;
        $this->userSettings = $this->userSession->authorizeSettingsModel($this->userModel->getId());

        return true;
    }

    public function loginAsRCP($pass, $email) {
        $this->logout();

        $this->loginAsUser($pass, $email);

        if ($this->isDeveloper()) {
            $this->userSession->setRCPLogged();
        }

        return $this->rcpLogged();
    }

    public function getEncodedData() {
        $preparedData = $this->userModel->getDataToStore();

        $preparedData['salt'] = mt_rand(0, 999);
        $preparedData['socket'] = '';
        $preparedData['settings'] = $this->userSettings->getDataToStore();

        $base64String = base64_encode(json_encode($preparedData));

        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        for($i = 40; $i >= 0; $i -= 2) {
            $base64String = substr_replace($base64String, $characters[mt_rand(0, 61)], $i, 0);
        }

        return $base64String;
    }

    static function shortNick($nick) {
        $nick2 = mb_substr(preg_replace('/[^A-ZА-Я]/mu', '', $nick), 0, 2);

        if(mb_strlen($nick2) < 1) {
            $nick2 = mb_strtoupper(mb_substr(preg_replace('/[^a-zа-я]/mu', '', $nick), 0, 2));
        }

        return $nick2;
    }
}