<?php
namespace Api\Session;

use Core\Extender\ApiAccessAjax;
use Core\Handler\MailSender;
use Core\Lib\AjaxFormResponse;
use Core\Lib\PasswordHash;

/**
 * Class RegistrationController
 *
 *
 * @package Core\Modules\User\Controller
 * @property \Core\Handler\User user
 * @property \Phalcon\Db\Adapter\Pdo\Mysql db
 */
class RegistrationController extends ApiAccessAjax
{
    private $langs = [
        1 => [
            'password_short' => 'Пароль не может быть короче пяти символов',
            'password_not_equal' => 'Пароли должны совпадать',
            'email_format' => 'Введенный адрес не является имейлом',
            'email_equal' => 'Необходимо указать одинаковые имейлы',
            'email_long'  => 'Имейл должен быть короче 100 символов',
            'email_exist' => 'Такой имейл уже занят',
            'email_free' => 'Имейл не занят',
            'nick_short' => 'Псевдоним не может быть короче 3х символов',
            'nick_long'  => 'Псевдоним не может быть длиннее 100 символов',
            'nick_exist' => 'Такой псевдоним уже занят',
            'nick_free'  => 'Псевдоним свободен',
            'date_error' => 'Указана не существующая дата!',
            'gender_error' => 'Необходимо указать пол!',
            'discord_code' => 'Что-то пошло не так',
            'discord_user' => 'Пользователь не найден'
        ],

        2 => [
            'password_short' => 'Password cannot be shorter than five characters',
            'password_not_equal' => 'Passwords must match',
            'email_format' => 'Entered address is not an email',
            'email_equal' => 'Emails must match',
            'email_long'  => 'Email must be shorter than 100 characters',
            'email_exist' => 'Email is already exists',
            'email_free' => 'Email is free',
            'nick_short' => 'Nickname cannot be shorter than 3 characters',
            'nick_long'  => 'Nickname cannot be longer than 100 characters',
            'nick_exist' => 'Nickname is already exists',
            'nick_free'  => 'Nickname is free',
            'date_error' => 'A nonexistent date is specified!',
            'gender_error' => 'You must specify the gender!',
            'discord_code' => 'Something went wrong',
            'discord_user' => 'User not found'
        ]
    ];


    /**
     * Проверка существования почтового адреса
     * $_POST['email1'] - почтовый адрес для проверки
     * $_POST['email2'] - почтовый адрес для проверки
     * @api
     */
    public function checkEmail()
    {
        $email1 = preg_replace("/\s+/", '', mb_strtolower(trim(htmlspecialchars($this->request->getPost('email1')))));
        $email2 = preg_replace("/\s+/", '', mb_strtolower(trim(htmlspecialchars($this->request->getPost('email2')))));

        $statusManager = new AjaxFormResponse();

        $this->validateEmail($email1, $email2, $statusManager);

        $this->sendResponseAjax([
            'fields' => $statusManager->getStatuses()
        ]);
    }

    /**
     * Проверка существования псевдонима
     * $_POST['nick'] - псевдоним для проверки
     * @api
     */
    public function checkNick()
    {
        $nick = $this->request->getPost('nick');

        $nick = trim($nick);
        $nick = preg_replace("/\s+/", " ", $nick);
        $nick = preg_replace("/-+/",  "-", $nick);
        $nick = preg_replace("/_+/",  "_", $nick);

        $nick = htmlspecialchars($nick);


        $statusManager = new AjaxFormResponse();

        $this->validateNick($nick, $statusManager);

        $this->sendResponseAjax([
            'fields' => $statusManager->getStatuses()
        ]);
    }

    /**
     * Регистрация нового пользователя
     * $_POST['nickname']    - псевдоним пользователя
     * $_POST['pass1']       - пароль
     * $_POST['pass2']       - подтверждение пароля
     * $_POST['mail1']      - контактные почтовый адрес
     * $_POST['mail2']      - подтверждение почтового адреса
     * $_POST['birth_day']   - день рождения
     * $_POST['birth_month'] - месяц рождения
     * $_POST['birth_year']  - год рождения
     * $_POST['gender']      - пол пользователя
     * @api
     */
    public function register()
    {
        $langs = $this->langs[$this->site->getLangId()];
        $nick = $this->request->getPost('nick');

        $nick = trim($nick);
        $nick = preg_replace("/\s+/", " ", $nick);
        $nick = preg_replace("/-+/",  "-", $nick);
        $nick = preg_replace("/_+/",  "_", $nick);

        $nick = htmlspecialchars($nick);

        $email1 = preg_replace("/\s+/", '', mb_strtolower(htmlspecialchars($this->request->getPost('mail1'))));
        $email2 = preg_replace("/\s+/", '', mb_strtolower(htmlspecialchars($this->request->getPost('mail2'))));

        $pass1 = trim($this->request->getPost('pass1'));
        $pass2 = trim($this->request->getPost('pass2'));


        $bDay   = (int)$this->request->getPost('birth_day');
        $bMonth = (int)$this->request->getPost('birth_month') + 1;
        $bYear  = (int)$this->request->getPost('birth_year');

        $gender  = trim($this->request->getPost('gender'));

        $statusManager = new AjaxFormResponse();

        $hasErrors = 0;
        $hasErrors += $this->validateEmail($email1, $email2, $statusManager);
        $hasErrors += $this->validateNick($nick, $statusManager);

        $responseMessage = '';

        if (!checkdate($bMonth, $bDay, $bYear)) {
            $responseMessage = $langs['date_error'];

            $hasErrors = 1;
        }

        if ($gender != 'male' && $gender != 'female') {
            $responseMessage = $langs['gender_error'];

            $hasErrors = 1;
        }

        if (mb_strlen($pass1) < 5 || mb_strlen($pass2) < 5) {
            $statusManager->setStatus('pass1', AjaxFormResponse::ERROR, $langs['password_short']);
            $statusManager->setStatus('pass2', AjaxFormResponse::ERROR, $langs['password_short']);
            $hasErrors = 1;
        }


        if ($pass1 != $pass2) {
            $statusManager->setStatus('pass1', AjaxFormResponse::ERROR, $langs['password_not_equal']);
            $statusManager->setStatus('pass2', AjaxFormResponse::ERROR, $langs['password_not_equal']);
            $hasErrors = 1;
        }

        // Есть ошибки
        if ($hasErrors) {
            $this->sendResponseAjax([
                'notification' => $responseMessage,
                'status' => 'no',
                'fields' => $statusManager->getStatuses()
            ]);
            return;
        }



        $passSalt = PasswordHash::passGenSalt();
        $passHash = PasswordHash::passGenHash($pass1, $email1, $passSalt);

        $activationCode = md5(mt_rand(0, 999).md5(mt_rand(0, 999)).mt_rand(1000, 2000).md5($email1));

        $memberId = (int)$this->db->query(
            '
                SELECT dp_members_register(
                    :email::email, 
                    :gender::varchar(10), 
                    :nick::varchar(100), 
                    :date::date, 
                    :lang::integer, 
                    :salt::varchar(64), 
                    :hash::hash_md5,
                    :key::varchar(32)
                ) AS mid
            ',
            [
                'email' => $email1,
                'gender' => $gender,
                'nick' => $nick,
                'date' => $bYear.'-'.$bMonth.'-'.$bDay,
                'lang' => $this->site->getLangId(),
                'salt' => $passSalt,
                'hash' => $passHash,
                'key' => $activationCode
            ]
        )->fetch()['mid'];

        if ($memberId) {

            $this->mdb->query(
                '
                    INSERT INTO `eva-project`.`members` ( 
                        `member_id`,
                        `member_email`, 
                        `member_gender`, 
                        `member_nick_original`, 
                        `member_nick_lowercase`, 
                        `member_date_birth`, 
                        `member_date_registrated` 
                    ) VALUES ( :mid, :email, :gender, :norig, :nlower, :birth, NOW() )
                ',
                [
                    'mid' => $memberId,
                    'email' => $email1,
                    'gender' => $gender,
                    'norig' => $nick,
                    'nlower' => mb_strtolower($nick, 'UTF8'),
                    'birth' => $bYear.'-'.$bMonth.'-'.$bDay
                ]
            );

            $this->mdb->query(
                'INSERT INTO `eva-project`.`members_auth` ( `member_id`, `auth_salt`, `auth_hash` ) VALUES (:mid, :salt, :hash)',
                [
                    'mid' => $memberId,
                    'salt' => $passSalt,
                    'hash' => $passHash
                ]
            );

            $this->mdb->query(
                '
                    INSERT INTO `fullres1_phalcon`.`scl_members` (
                        `member_id`, 
                        `member_email`, 
                        `member_gender`, 
                        `member_nick_original`, 
                        `member_nick_lowercase`, 
                        `member_date_birth`, 
                        `member_date_registrated` 
                    ) VALUES (:mid, :email, :gender, :norig, :nlower, :birth, NOW())
                ',
                [
                    'mid' => $memberId,
                    'email' => $email1,
                    'gender' => $gender,
                    'norig' => $nick,
                    'nlower' => mb_strtolower($nick, 'UTF8'),
                    'birth' => $bYear.'-'.$bMonth.'-'.$bDay
                ]
            );

            $this->mdb->query(
                'INSERT INTO `fullres1_phalcon`.`scl_members_auth` ( `member_id`, `auth_salt`, `auth_hash` ) VALUES (:mid, :salt, :hash)',
                [
                    'mid' => $memberId,
                    'salt' => $passSalt,
                    'hash' => $passHash
                ]
            );

            $mailSender = new MailSender('auth_activation', $this->site->getLangId());

            $mailSender->send($memberId, [
                'activationLink' => 'https://'.$_SERVER['HTTP_HOST'].'/#auth/activate/'.$activationCode,
            ]);
        }

        $this->sendResponseAjax([
            'status' => 'yes',
            'fields' => []
        ]);
    }

    public function discord() {
        $langs = $this->langs[$this->site->getLangId()];

        $statusManager = new AjaxFormResponse();
        $hasErrors = 0;
        $responseMessage = '';

        if (!$this->session->has('discord_code') || $this->user->getId()) {
            $this->sendResponseAjax([
                'notification' => $langs['discord_code'],
                'status' => 'no',
                'fields' => $statusManager->getStatuses()
            ]);
        }

        $bDay   = (int)$this->request->getPost('day');
        $bMonth = (int)$this->request->getPost('month');
        $bYear  = (int)$this->request->getPost('year');

        $gender  = trim($this->request->getPost('gender'));

        if (!checkdate($bMonth, $bDay, $bYear)) {
            $responseMessage = $langs['date_error'];

            $hasErrors = 1;
        }

        if ($gender != 'male' && $gender != 'female') {
            $responseMessage = $langs['gender_error'];

            $hasErrors = 1;
        }

        // Есть ошибки
        if ($hasErrors) {
            $this->sendResponseAjax([
                'notification' => $responseMessage,
                'status' => 'no',
                'fields' => $statusManager->getStatuses()
            ]);
            return;
        }

        $discordUser = $this->db->query(
            '
                SELECT 
                    du.discord_username,
                    du.discord_discriminator,
                    du.discord_email 
                FROM 
                    public.discord_user_oauth2 AS dua,
                    public.discord_user AS du 
                WHERE
                    dua.discord_code = :code AND 
                    du.discord_user_id = dua.discord_user_id
            ',
            [
                $this->session->get('discord_code')
            ]
        )->fetch();

        if (!$discordUser) {
            $this->sendResponseAjax([
                'notification' => $langs['discord_user'],
                'status' => 'no',
                'fields' => $statusManager->getStatuses()
            ]);
            return;
        }

        $nick = htmlspecialchars($discordUser['discord_username'].'#'.$discordUser['discord_discriminator']);
        $email = $discordUser['discord_email'];
        $pass = PasswordHash::generatePassword(10);

        $passSalt = PasswordHash::passGenSalt();
        $passHash = PasswordHash::passGenHash($pass, $email, $passSalt);


        $memberId = (int)$this->db->query(
            '
                SELECT public.dp_members_register_discord(
                    :email::email, 
                    :gender::varchar(10), 
                    :nick::varchar(100), 
                    :date::date, 
                    :lang::integer, 
                    :salt::varchar(64), 
                    :hash::hash_md5
                ) AS mid
            ',
            [
                'email' => $email,
                'gender' => $gender,
                'nick' => $nick,
                'date' => $bYear.'-'.$bMonth.'-'.$bDay,
                'lang' => $this->site->getLangId(),
                'salt' => $passSalt,
                'hash' => $passHash
            ]
        )->fetch()['mid'];

        if ($memberId) {
            /*
            $this->mdb->query(
                '
                    INSERT INTO `eva-project`.`members` ( 
                        `member_id`,
                        `member_email`, 
                        `member_gender`, 
                        `member_nick_original`, 
                        `member_nick_lowercase`, 
                        `member_date_birth`, 
                        `member_date_registrated`,
                        `member_mgroup`
                    ) VALUES ( :mid, :email, :gender, :norig, :nlower, :birth, NOW(), 2)
                ',
                [
                    'mid' => $memberId,
                    'email' => $email,
                    'gender' => $gender,
                    'norig' => $nick,
                    'nlower' => mb_strtolower($nick, 'UTF8'),
                    'birth' => $bYear.'-'.$bMonth.'-'.$bDay
                ]
            );

            $this->mdb->query(
                'INSERT INTO `eva-project`.`members_auth` ( `member_id`, `auth_salt`, `auth_hash` ) VALUES (:mid, :salt, :hash)',
                [
                    'mid' => $memberId,
                    'salt' => $passSalt,
                    'hash' => $passHash
                ]
            );

            $this->mdb->query(
                '
                    INSERT INTO `fullres1_phalcon`.`scl_members` (
                        `member_id`, 
                        `member_email`, 
                        `member_gender`, 
                        `member_nick_original`, 
                        `member_nick_lowercase`, 
                        `member_date_birth`, 
                        `member_date_registrated`,
                        `member_mgroup` 
                    ) VALUES (:mid, :email, :gender, :norig, :nlower, :birth, NOW(), 2)
                ',
                [
                    'mid' => $memberId,
                    'email' => $email,
                    'gender' => $gender,
                    'norig' => $nick,
                    'nlower' => mb_strtolower($nick, 'UTF8'),
                    'birth' => $bYear.'-'.$bMonth.'-'.$bDay
                ]
            );

            $this->mdb->query(
                'INSERT INTO `fullres1_phalcon`.`scl_members_auth` ( `member_id`, `auth_salt`, `auth_hash` ) VALUES (:mid, :salt, :hash)',
                [
                    'mid' => $memberId,
                    'salt' => $passSalt,
                    'hash' => $passHash
                ]
            );*/

            $mailSender = new MailSender('registration_discord', $this->site->getLangId());

            $mailSender->send($memberId, [
                'temporaryPassword' => $pass,
            ]);

            $this->user->loginById($memberId);
        }

        $this->sendResponseAjax([
            'status' => 'yes',
            'fields' => []
        ]);
    }

    private function validateEmail(string $email1, string $email2, AjaxFormResponse &$statusManager): int {
        $langs = $this->langs[$this->site->getLangId()];

        $hasErrors = 0;

        if(!preg_match( "/^.+@.+\..+$/ui", $email1)) {
            $statusManager->setStatus('mail1', AjaxFormResponse::ERROR, $langs['email_format']);
            $hasErrors = 1;
        }

        if(!preg_match( "/^.+@.+\..+$/ui", $email2)) {
            $statusManager->setStatus('mail2', AjaxFormResponse::ERROR, $langs['email_format']);
            $hasErrors = 1;
        }

        if ($email1 != $email2) {
            $statusManager->setStatus('mail1', AjaxFormResponse::ERROR, $langs['email_equal']);
            $statusManager->setStatus('mail2', AjaxFormResponse::ERROR, $langs['email_equal']);
            $hasErrors = 1;
        }

        if (!$hasErrors && mb_strlen($email1) > 98) {
            $statusManager->setStatus('mail1', AjaxFormResponse::ERROR, $langs['email_long']);
            $statusManager->setStatus('mail2', AjaxFormResponse::ERROR, $langs['email_long']);
            $hasErrors = 1;
        }

        if (!$hasErrors && $this->db->query(
                'SELECT 1 FROM members WHERE member_email = LOWER(:email)', ['email' => $email1]
            )->fetch()) {
            $statusManager->setStatus('mail1', AjaxFormResponse::ERROR, $langs['email_exist']);
            $statusManager->setStatus('mail2', AjaxFormResponse::ERROR, $langs['email_exist']);
            $hasErrors = 1;
        }

        if (!$hasErrors) {
            $statusManager->setStatus('mail1', AjaxFormResponse::SUCCESS, $langs['email_free']);
            $statusManager->setStatus('mail2', AjaxFormResponse::SUCCESS, $langs['email_free']);
        }

        return $hasErrors;
    }

    private function validateNick(string $nick, AjaxFormResponse &$statusManager): int {
        $langs = $this->langs[$this->site->getLangId()];

        $hasErrors = 0;

        $nickLen = mb_strlen($nick);

        if($nickLen < 3) {
            $statusManager->setStatus('nick', AjaxFormResponse::ERROR, $langs['nick_short']);
            $hasErrors = 1;
        }


        if($nickLen > 98) {
            $statusManager->setStatus('nick', AjaxFormResponse::ERROR, $langs['nick_long']);
            $hasErrors = 1;
        }

        if (!$hasErrors && $this->db->query(
                'SELECT 1 FROM members WHERE member_nick_lower = LOWER(:nick)', ['nick' => $nick]
            )->fetch()) {
            $statusManager->setStatus('nick', AjaxFormResponse::ERROR, $langs['nick_exist']);
            $hasErrors = 1;
        }

        if (!$hasErrors) {
            $statusManager->setStatus('nick', AjaxFormResponse::SUCCESS, $langs['nick_free']);
        }

        return $hasErrors;
    }
}