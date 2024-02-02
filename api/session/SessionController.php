<?php
namespace Api\Session;

use Core\Extender\ApiAccessAjax;
use Core\Lib\AjaxFormResponse;

class SessionController extends ApiAccessAjax
{
    public function login() {
        if ($this->user->isLogged()) {
            $this->sendResponseAjax([], 0);
            return;
        }

        $login = trim($this->request->getPost('login'));
        $pass = trim($this->request->getPost('pass'));
        $status = 'no';
        $formStatus = AjaxFormResponse::ERROR;
        $formText   = 'Неверные логин/пароль';

        $statusManager = new AjaxFormResponse();


        /*echo $this->ldapLogin($login, $pass);
        exit;*/

        if ($login != '' && $pass != '' && $this->ldapLogin($login, $pass)) {
            $memberId = (int)$this->db->query(
                '
                    SELECT am.member_id
                    FROM integration.auth AS a
                       , integration.auth_member AS am
                         LEFT JOIN public.members_groups AS mg 
                                ON mg.member_id = am.member_id 
                               AND mg.group_id = 5 
                    WHERE a.auth_name = :auth
                      AND am.auth_id = a.auth_id
                      AND LOWER(am.auth_login) = LOWER(:login) 
                      AND mg.group_id IS NULL --Если нет группы, то человек не забанен = АКТИВЕН
                ',
                [
                    'auth' => 'ldap',
                    'login' => $login
                ]
            )->fetch()['member_id'];


            /*if ($memberId == 997) {
                $memberId = 3363;
            }*/

            if ($memberId) {
                $this->user->loginById($memberId);

                $status = 'yes';
                $formStatus = AjaxFormResponse::SUCCESS;
                $formText   = '';

            }
        }

        $statusManager->setStatus('login', $formStatus, $formText);
        $statusManager->setStatus('pass', $formStatus, $formText);



        $this->sendResponseAjax([
            'auth'   => $status,
            'fields' => $statusManager->getStatuses()
        ]);
    }

    public function logout() {
        $this->user->logout();

        $this->sendResponseAjax();
    }


    private function ldapLogin($username, $password) {
        $adServer = "ldap://ns-dc1.nauka.net";

        $ldap = ldap_connect($adServer);

        $ldaprdn = 'nauka' . "\\" . $username;


        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
        return @ldap_bind($ldap, $ldaprdn, $password) ? 1 : 0;
    }
}