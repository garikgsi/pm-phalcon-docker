<?php
namespace Core\Lib;

use Phalcon\DI;

class PasswordHash
{

    public static function passGenSalt()
    {
        $salt  = '';

        for($a = 0; $a < 20; $a++)
            $salt .= chr(rand(48, 122));

        return htmlspecialchars($salt);
    }

    public static function passGenHash($pass, $email, $salt)
    {
        return md5(md5($pass).mb_strtolower($email, 'UTF-8').sha1($salt));
    }

    public static function tryToLogIn($pass, $email) {
        $db = DI::getDefault()['db'];

        $email = mb_strtolower($email);

        $m = $db->query(
            '
                SELECT 
                    m.member_id,
                    ma.auth_salt,
                    ma.auth_hash
                FROM
                    members AS m,
                    members_auth AS ma
                WHERE
                    ma.member_id = m.member_id AND 
                    m.member_email = :mail      
            ',
            ['mail' => $email]
        )->fetch();

        if (!$m) {
            return 0;
        }

        return self::passGenHash($pass, $email, $m['auth_salt']) == $m['auth_hash'] ? (int)$m['member_id'] : 0;
    }

    public static function comparePasswords($pass, $memberId) {
        $db = DI::getDefault()['db'];

        $memberRow = $db->query('SELECT * FROM public.members WHERE member_id = :mid', ['mid' => $memberId])->fetch();

        $logs = $db->query('SELECT * FROM public.members_log_auth WHERE member_id = :mid', ['mid' => $memberId]);

        //Пробегаемся по логам

        while ($row = $logs->fetch()) {
            if (self::compare2Passwords($pass, $row['member_email'], $row['auth_salt'], $row['auth_hash']))
                return true;
        }

        return false;
    }

    private static function compare2Passwords($pass, $email, $oldSalt, $oldHash) {
        return self::passGenHash($pass, $email, $oldSalt) == $oldHash ? true : false;
    }



    public static function generatePassword($length){
        return mb_substr(
            preg_replace("/[^a-zA-Z0-9]/", "", base64_encode(
                self::getRandomBytes($length+1)
            )),0,$length
        );
    }

    private static function getRandomBytes($nbBytes = 32) {
        $bytes = openssl_random_pseudo_bytes($nbBytes, $strong);

        if (false !== $bytes && true === $strong) {
            return $bytes;
        }
        else {
            throw new \Exception("Unable to generate secure token from OpenSSL.");
        }
    }





}