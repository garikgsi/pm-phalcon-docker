<?php
namespace Api\Session;

use Core\Extender\ApiAccessAjax;
use Core\Handler\MailSender;
use Core\Lib\AjaxFormResponse;
use Core\Lib\PasswordHash;

class ReminderController extends ApiAccessAjax
{
    private $langs = [
        1 => [
            'email_short' => 'Введенный адрес не является имейлом',
            'email_long'  => 'Имейл должен быть короче 100 символов',
            'email_free'  => 'Имейл доступен',
            'email_no_exist' => 'Такой имейл не существует',

            'password_short' => 'Пароль не может быть короче пяти символов',
            'password_equal' => 'Пароли должны совпадать',
            'password_key'   => 'Ключ для смены пароля устарел'
        ],

        2 => [
            'email_short' => 'Entered address is not an email',
            'email_long'  => 'Email must be shorter than 100 characters',
            'email_free'  => 'Email is free',
            'email_no_exist' => 'Email does not exist',

            'password_short' => 'Password cannot be shorter than five character',
            'password_equal' => 'Passwords must match',
            'password_key'   => 'Password change key is out of date'
        ]
    ];

    public function send() {
        if ($this->user->isLogged()) {
            $this->sendResponseAjax([], 0);
            return;
        }

        $langs = $this->langs[$this->site->getLangId()];

        $email = preg_replace("/\s+/", '', mb_strtolower(trim(htmlspecialchars($this->request->getPost('email')))));

        $statusManager = new AjaxFormResponse();

        $hasErrors = $this->validateEmailAvailable($email, $statusManager, $this->validateEmail($email, $statusManager));

        if (!$hasErrors) {
            $activationCode = md5(mt_rand(0, 999).md5(mt_rand(0, 999)).mt_rand(1000, 2000).md5($email));

            $memberId = (int)$this->db->query(
                'SELECT dp_members_reminder_create(:email::email, :key::varchar(32)) AS mid',
                [
                    'email' => $email,
                    'key'   => $activationCode
                ]
            )->fetch()['mid'];

            if ($memberId) {
                $mailSender = new MailSender('auth_reminder', $this->site->getLangId());

                $mailSender->send($memberId, [
                    'activationLink' => 'https://'.$_SERVER['HTTP_HOST'].'/#auth/remind/'.$activationCode,
                ]);
            }
            else {
                $statusManager->setStatus('email', AjaxFormResponse::ERROR, $langs['email_no_exist']);
                $hasErrors = 1;
            }
        }

        $this->sendResponseAjax([
            'status' => $hasErrors ? 'no' : 'yes',
            'fields' => $statusManager->getStatuses()
        ]);
    }

    public function change() {
        $langs = $this->langs[$this->site->getLangId()];

        $pass1 = trim($this->request->getPost('pass1'));
        $pass2 = trim($this->request->getPost('pass2'));

        $code  = trim($this->request->get('code'));

        $statusManager   = new AjaxFormResponse();
        $responseMessage = '';

        $hasErrors = 0;
        $codeRow   = [];

        if (mb_strlen($pass1) < 5 || mb_strlen($pass2) < 5) {
            $statusManager->setStatus('pass1', AjaxFormResponse::ERROR, $langs['password_short']);
            $statusManager->setStatus('pass2', AjaxFormResponse::ERROR, $langs['password_short']);
            $hasErrors = 1;
        }


        if ($pass1 != $pass2) {
            $statusManager->setStatus('pass1', AjaxFormResponse::ERROR, $langs['password_equal']);
            $statusManager->setStatus('pass2', AjaxFormResponse::ERROR, $langs['password_equal']);
            $hasErrors = 1;
        }

        if (!$hasErrors) {
            $codeRow = $this->db->query(
                '
                SELECT 
                    mk.member_id,
                    m.member_email 
                FROM 
                    members AS m,
                    members_keys AS mk 
                WHERE 
                    m.member_id = mk.member_id AND
                    mk.key_value = :code AND 
                    mk.key_type = :type
            ',
                [
                    'type' => 'reminder',
                    'code' => $code
                ]
            )->fetch();

            if (!$codeRow) {
                $hasErrors = 1;
                $responseMessage = $langs['password_key'];
            }
        }

        if (!$hasErrors) {
            $salt = PasswordHash::passGenSalt();
            $hash = PasswordHash::passGenHash($pass1, $codeRow['member_email'], $salt);

            $changeStatus = (int)$this->db->query(
                'SELECT dp_members_reminder_change(:code::varchar(32), :salt::varchar(64), :hash::hash_md5) AS res',
                [
                    'code' => $code,
                    'salt' => $salt,
                    'hash' => $hash
                ]
            )->fetch()['res'];

            if ($changeStatus == 1) {
                $hasErrors = 1;
                $responseMessage = $langs['password_key'];
            }
            else {
                $mailSender = new MailSender('auth_reminder_complete', $this->site->getLangId());

                $mailSender->send($codeRow['member_id']);
            }
        }

        $this->sendResponseAjax([
            'notification' => $responseMessage,
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

    public function checkCode()
    {
        $key = trim(htmlspecialchars($this->request->getPost('key')));

        $keyRow = $this->db->query(
            'SELECT member_id FROM members_keys WHERE key_value = :key AND key_type = :type',
            [
                'type' => 'reminder',
                'key'  => $key
            ]
        )->fetch();

        $this->sendResponseAjax(['status' => $keyRow ? 'yes' : 'no']);
    }



    private function validateEmail($email, AjaxFormResponse &$statusManager) {
        $langs = $this->langs[$this->site->getLangId()];

        $hasErrors = 0;

        if(!preg_match( "/^.+@.+\..+$/ui", $email)) {
            $statusManager->setStatus('email', AjaxFormResponse::ERROR, $langs['email_short']);
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

        if (!$hasErrors && !$this->db->query(
                'SELECT 1 FROM members WHERE member_email = LOWER(:email)', ['email' => $email]
            )->fetch()) {
            $statusManager->setStatus('email', AjaxFormResponse::ERROR, $langs['email_no_exist']);
            $hasErrors = 1;
        }

        if (!$hasErrors) {
            $statusManager->setStatus('email', AjaxFormResponse::SUCCESS, $langs['email_free']);
        }

        return $hasErrors;
    }






}