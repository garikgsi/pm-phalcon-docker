<?php
namespace Api\Session;


use Core\Extender\ApiAccessAjax;
use Core\Handler\MailSender;
use Core\Lib\AjaxFormResponse;
use Core\Lib\PasswordHash;

/**
 * Class ActivationController
 * Контроллер активации пользователей после регистрации
 *
 * @package Core\Modules\User\Controller
 * @property \Core\Handler\User user
 * @property \Phalcon\Db\Adapter\Pdo\Mysql db
 */
class ActivationController extends ApiAccessAjax
{
    private $langs = [
        1 => [
            'password_invalid' => 'Введен неверный пароль',
            'email_format' => 'Введенный адрес не является имейлом',
            'email_long' => 'Имейл должен быть короче 100 символов',
            'email_exists' => 'Такой имейл уже занят',
            'email_free' => 'Имейл доступен',
            'email_active' => 'Имейл не ожидает активации',
            'email_inactive' => 'Имейл ожидает активацию',

            'result_1' => 'Данный пользователь уже активирован или не существует!',
            'result_2' => 'Новый почтовый адрес уже занят, придумайте другой!'
        ],

        2 => [
            'password_invalid' => 'Password is incorrect',
            'email_format' => 'Entered address is not an email',
            'email_long' => 'Email must be shorter than 100 characters',
            'email_exists' => 'Email is already exists',
            'email_free' => 'Email is free',
            'email_active' => 'Email does not wait for activation',
            'email_inactive' => 'Email awaiting activation',

            'result_1' => 'This user is already activated or does not exist!',
            'result_2' => 'The new postal address is already occupied, come up with another!'
        ]
    ];

    public function activate() {
        $key = trim(htmlspecialchars($this->request->getPost('key')));

        $memberId = (int)$this->db->query(
            'SELECT dp_members_activation(:key) AS status',
            ['key' => $key]
        )->fetch()['status'];

        if ($memberId != 0 ) {
            if ($this->user->isLogged()) {
                $this->user->refresh();
            }
            else {
                $this->user->loginById($memberId);
            }


            $this->mdb->query('UPDATE `fullres1_phalcon`.`scl_members` SET `member_mgroup` = 2 WHERE `member_id` = '.$memberId);
            $this->mdb->query('UPDATE `eva-project`.`members` SET `member_mgroup` = 2 WHERE `member_id` = '.$memberId);


            $mailSender = new MailSender('auth_activation_complete', $this->site->getLangId());

            $mailSender->send($memberId);
        }

        $this->sendResponseAjax(['status' => $memberId != 0 ? 'yes' : 'no']);
    }

    public function resend()
    {
        $langs = $this->langs[$this->site->getLangId()];
        $email = preg_replace("/\s+/", '', mb_strtolower(trim(htmlspecialchars($this->request->getPost('email')))));

        $statusManager = new AjaxFormResponse();

        $hasErrors = $this->validateEmailResendable($email, $statusManager, $this->validateEmail($email, $statusManager));

        if (!$hasErrors) {
            $keyRow = $this->db->query(
                '
                    SELECT 
                        m.member_id,
                        mk.key_value 
                    FROM 
                        members AS m, 
                        members_keys AS mk
                    WHERE
                        m.member_email = :email AND
                        m.member_group = 4 AND
                        mk.member_id = m.member_id AND
                        mk.key_type = \'activation\'
                ',
                ['email' => $email]
            )->fetch();

            if (!$keyRow) {
                $statusManager->setStatus('email', AjaxFormResponse::ERROR, $langs['email_active']);
                $hasErrors = 1;
            }
            else {
                $mailSender = new MailSender('auth_activation', $this->site->getLangId());

                $mailSender->send((int)$keyRow['member_id'], [
                    'activationLink' => 'https://'.$_SERVER['HTTP_HOST'].'/#auth/activate/'.$keyRow['key_value'],
                ]);
            }
        }

        $this->sendResponseAjax([
            'status' => $hasErrors ? 'no' : 'yes',
            'fields' => $statusManager->getStatuses()
        ]);
    }

    public function widget() {
        // Не зареганные / активированные не имеют доступа к этой функции
        if (!$this->user->isLogged() || $this->user->isActive()) {
            $this->sendResponseAjax([], 0);
            return;
        }

        $statusManager = new AjaxFormResponse();

        $memberId = $this->user->getId();

        $email = preg_replace("/\s+/", '', mb_strtolower(htmlspecialchars($this->request->getPost('email'))));

        // Делаем ресенд текущей активации!
        if ($email == '') {
            $activationCode = md5(mt_rand(0, 999).md5(mt_rand(0, 999)).mt_rand(1000, 2000).md5($this->user->getEmail()));


            $mailSender = new MailSender('auth_activation', $this->site->getLangId());

            $this->db->query(
                '
                    INSERT INTO members_keys (member_id, key_type, key_value) 
                    VALUES (:mid, :type, :key) 
                    ON CONFLICT(member_id, key_type) 
                    DO UPDATE SET key_value = EXCLUDED.key_value
                ',
                [
                    'mid'  => $memberId,
                    'type' => 'activation',
                    'key'  => $activationCode
                ]
            );

            $mailSender->send($memberId, [
                'activationLink' => 'https://'.$_SERVER['HTTP_HOST'].'/#auth/activate/'.$activationCode,
            ]);


            $this->sendResponseAjax([
                'status' => 'yes',
                'fields' => $statusManager->getStatuses()
            ]);
        }



        $hasErrors = $this->validateEmailAvailable($email, $statusManager, $this->validateEmail($email, $statusManager));

        if (!$hasErrors) {
            $activationCode = md5(mt_rand(0, 999).md5(mt_rand(0, 999)).mt_rand(1000, 2000).md5('activatechange'.$this->user->getEmail()));

            $mailSender = new MailSender('auth_activation_mail', $this->site->getLangId());

            $this->db->query(
                'SELECT dp_members_activation_mail_change(:mid::bigint, :mail::email, :key::varchar(32))',
                [
                    'mid'  => $memberId,
                    'mail' => $email,
                    'key'  => $activationCode
                ]
            );

            $mailSender->send($memberId, [
                'activationLink' => 'https://'.$_SERVER['HTTP_HOST'].'/#auth/activate_mail/'.$activationCode,
            ], $email);
        }


        $this->sendResponseAjax([
            'status' => $hasErrors ? 'no' : 'yes',
            'fields' => $statusManager->getStatuses()
        ]);
    }


    public function checkEmail()
    {
        $email = preg_replace("/\s+/", '', mb_strtolower(trim(htmlspecialchars($this->request->getPost('email')))));

        $statusManager = new AjaxFormResponse();

        $this->validateEmailAvailable($email, $statusManager, $this->validateEmail($email, $statusManager));

        $this->sendResponseAjax([
            'fields' => $statusManager->getStatuses()
        ]);
    }

    public function checkEmailResend() {
        $email = preg_replace("/\s+/", '', mb_strtolower(trim(htmlspecialchars($this->request->getPost('email')))));

        $statusManager = new AjaxFormResponse();

        $this->validateEmailResendable($email, $statusManager, $this->validateEmail($email, $statusManager));

        $this->sendResponseAjax([
            'fields' => $statusManager->getStatuses()
        ]);
    }


    public function confirmMailChange() {
        $status = 'yes';

        $memberId = (int)$this->db->query(
            'SELECT public.dp_members_change_email(:key) AS res',
            ['key' => trim(htmlspecialchars($this->request->get('key')))]
        )->fetch()['res'];

        if (!$memberId) {
            $status = 'no';
        }
        else {
            $this->user->loginById($memberId);
        }

        $this->sendResponseAjax([
            'status' => $status
        ]);
    }

    public function activateMail() {
        $key  = trim(htmlspecialchars($this->request->get('key')));
        $pass = $this->request->getPost('pass');
        $status = 'yes';

        $langs = $this->langs[$this->site->getLangId()];

        $mailSender = new MailSender('auth_activation_complete', $this->site->getLangId());

        $resultMessages = [
            1 => $langs['result_1'],
            2 => $langs['result_2']
        ];

        $notification = '';

        $statusManager = new AjaxFormResponse();

        $keyRow = $this->db->query(
            'SELECT * FROM dp_members_activation_mail_change_check(:key::varchar(32))',
            ['key' => $key]
        )->fetch();

        if (!$keyRow) {
            $this->sendResponseAjax(['status' => 'no']);
            return;
        }

        $authMemberId = PasswordHash::tryToLogIn($pass, $keyRow['member_email']);

        if (!$authMemberId) {
            $statusManager->setStatus('pass', AjaxFormResponse::ERROR, $langs['password_invalid']);
            $status = 'no';
        }
        else {
            $salt = PasswordHash::passGenSalt();
            $hash = PasswordHash::passGenHash($pass, $keyRow['new_email'], $salt);

            $result = (int)$this->db->query(
                'SELECT dp_members_activation_mail_change_swap(:mid::bigint, :mail::email, :salt::varchar(64), :hash::hash_md5) AS res',
                [
                    'mid'  => $authMemberId,
                    'mail' => $keyRow['new_email'],
                    'salt' => $salt,
                    'hash' => $hash
                ]
            )->fetch()['res'];

            if ($result != 0) {
                $notification = $resultMessages[$result];
                $status = 'no';
            }
            else {
                $mailSender = new MailSender('auth_activation_complete', $this->site->getLangId());

                $mailSender->send($authMemberId);

                if ($this->user->isLogged()) {
                    $this->user->refresh();
                }
                else {
                    $this->user->loginById($authMemberId);
                }
            }
        }


        $this->sendResponseAjax([
            'notification' => $notification,
            'status' => $status,
            'fields' => $statusManager->getStatuses()
        ]);
    }

    public function activateMailCheck() {
        $keyRow = $this->db->query(
            'SELECT * FROM dp_members_activation_mail_change_check(:key::varchar(32))',
            ['key' => trim(htmlspecialchars($this->request->getPost('key')))]
        )->fetch();

        if (!$keyRow) {
            $this->sendResponseAjax(['status' => 'no']);
            return;
        }

        $this->sendResponseAjax([
            'status' => 'yes',
            'mid'    => $keyRow['member_id'],
            'email'  => $keyRow['new_email']
        ]);
    }




    private function validateEmail($email, AjaxFormResponse &$statusManager) {
        $langs = $this->langs[$this->site->getLangId()];

        $hasErrors = 0;

        if(!preg_match( "/^.+@.+\..+$/ui", $email)) {
            $statusManager->setStatus('email', AjaxFormResponse::ERROR, $langs['email_format']);
            $hasErrors = 1;
        }

        if (!$hasErrors && mb_strlen($email) > 98) {
            $statusManager->setStatus('email', AjaxFormResponse::ERROR, $langs['email_long']);
            $hasErrors = 1;
        }

        return $hasErrors;
    }

    private function validateEmailAvailable($email, AjaxFormResponse &$statusManager, $hasErrors = 0) {
        $langs = $this->langs[$this->site->getLangId()];

        if (!$hasErrors && $this->db->query(
                'SELECT 1 FROM members WHERE member_email = LOWER(:email)', ['email' => $email]
            )->fetch()) {
            $statusManager->setStatus('email', AjaxFormResponse::ERROR, $langs['email_exists']);
            $hasErrors = 1;
        }

        if (!$hasErrors) {
            $statusManager->setStatus('email', AjaxFormResponse::SUCCESS, $langs['email_free']);
        }

        return $hasErrors;
    }

    private function validateEmailResendable($email, AjaxFormResponse &$statusManager, $hasErrors = 0) {
        $langs = $this->langs[$this->site->getLangId()];

        if (!$hasErrors && !$this->db->query(
                'SELECT 1 FROM members WHERE member_email = LOWER(:email) AND member_group = 4', ['email' => $email]
            )->fetch()) {
            $statusManager->setStatus('email', AjaxFormResponse::ERROR, $langs['email_active']);
            $hasErrors = 1;
        }

        if (!$hasErrors) {
            $statusManager->setStatus('email', AjaxFormResponse::SUCCESS, $langs['email_inactive']);
        }

        return $hasErrors;
    }
}