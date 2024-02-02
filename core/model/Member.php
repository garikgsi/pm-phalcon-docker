<?php
namespace Core\Model;

use Core\Driver\MemberSql;
use Phalcon\DI;

/**
 * @property \Phalcon\Db\Adapter\Pdo\Postgresql db
 */
class Member
{
    protected $id = 0;
    protected $nick = '';
    protected $age = 12;
    protected $group = \Core\Dict\MemberGroups::GUESTS;
    protected $gender = 'male';
    protected $email = '';
    protected $avatar = 0;
    protected $avatarType = '';

    protected $groups = [\Core\Dict\MemberGroups::GUESTS];
    protected $rights = [];
    protected $rpgi = [];
    protected $access = ['auth' => 1];

    protected $meta = [];

    protected $db;


    public static function initBySessionData($sessionData) {
        return (new self())->fillDataFromSession($sessionData);
    }

    public static function initByCookieKey($cookieKey) {
        return (new self())->fillDataFromCookie($cookieKey);
    }

    public static function initByMemberId($memberId) {
        return (new self())->fillDataFromMemberId($memberId);
    }

    protected function __construct() {// Скрываем публичный конструктор, все идет только через статические методы
        ;
    }

    public function fillDataFromSession($sessionData) {
        return $this->fillDataFromStandardContainer($sessionData);
    }

    public function fillDataFromCookie($cookieKey) {
        /*if (!preg_match( '/^[a-zA-Z0-9]+$/ui', $cookieKey)) {
            $this->fillDataFromMemberId(0);
            return $this;
        }*/

        $memberId = (int)$this->getMemberIdByCookieKey($cookieKey);

        //if ($memberId) {
        $this->fillDataFromMemberId($memberId);
        //}

        return $this;
    }

    public function fillDataFromMemberId($memberId) {
        if (!$memberId) {
            $this->fillFromGuest();
            return $this;
        }

        $member = MemberSql::dbMemberSelect($memberId);

        if (!$member) {
            return $this;
        }

        $member['age'] = (int)substr(
            date('Ymd') - date('Ymd', strtotime($member['age'])), 0, -4
        );

        $member['avatar'] = (int)$member['avatar'];

        return $this->fillDataFromStandardContainer($member);
    }

    public function fillFromGuest() {
        //$this->rpgi = MemberSql::dbMemberRPGIData(0);
        $this->access = MemberSql::dbMemberAccess(0, $this->group, $this->groups);
        $this->rights = MemberSql::dbMemberRights(0);
    }

    protected function getMemberIdByCookieKey($cookieKey) {
        return MemberSql::dbCookieSelect($cookieKey);
    }

    private function fillDataFromStandardContainer($memberData) {
        $this->id = (int)$memberData['id'];
        $this->nick = $memberData['nick'];
        $this->age = (int)$memberData['age'];
        $this->group = (int)$memberData['group'];
        $this->groups = $memberData['groups'];
        $this->access = $memberData['access'];
        $this->gender = $memberData['gender'];
        $this->email = $memberData['email'];
        $this->avatar = $memberData['avatar'];
        $this->avatarType = $memberData['avatar_type'];

        $this->rights = $memberData['rights'];
        //$this->rpgi = $memberData['rpgi'];

        unset($memberData['id'], $memberData['nick'], $memberData['age'],
            $memberData['group'], $memberData['gender'], $memberData['email'],
            $memberData['rights'], $memberData['access'], $memberData['groups'],
            $memberData['rpgi'], $memberData['avatar'], $memberData['avatar_type'], $memberData['meta']
        );

        $this->meta = $memberData;

        return $this;
    }

    public function addCookie($cookieKey) {
        MemberSql::dbCookieInsert($this->getId(), $cookieKey);
    }

    public function getId() {
        return $this->id;
    }

    public function getNick() {
        return $this->nick;
    }

    public function getGroup() {
        return $this->group;
    }

    public function getGroups() {
        return $this->groups;
    }

    public function getAccess() {
        return $this->access;
    }

    public function getAge() {
        return $this->age;
    }

    public function getGender() {
        return $this->gender;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getAvatar() {
        return $this->avatar;
    }

    public function getAvatarType() {
        return $this->avatarType;
    }

    public function getMetaData($metaKey) {
        return $this->meta[$metaKey] ?? false;
    }

    public function getRPGIData() {
        return $this->rpgi;
    }

    public function getDataToStore() {
        return [
            'id' => $this->getId(),
            'nick' => $this->getNick(),
            'age' => $this->getAge(),
            'group' => $this->getGroup(),
            'groups' => $this->getGroups(),
            'access' => $this->getAccess(),
            'gender' => $this->getGender(),
            'email' => $this->getEmail(),
            'avatar' => $this->getAvatar(),
            'avatar_type' => $this->getAvatarType(),
            'meta' => $this->meta,
            'rights' => $this->rights,
            'rpgi' => $this->rpgi
        ];
    }

    public function getRightsList() {
        return $this->rights;
    }

    public function hasRight($rightName) {
        return isset($this->rights[$rightName]);
    }

    public function refreshData($cookieKey) {
        $this->fillDataFromCookie($cookieKey);
        //$this->fillDataFromMemberId($this->id);
    }
}