<?php
namespace Core\Handler;

use Phalcon\Di\Injectable;
use PHPMailer\PHPMailer\PHPMailer;

class MailSender extends Injectable
{
    private static $emailFrom  = ['mail' => '', 'name' => '', 'sig' => ''];
    private static $emailReply = ['mail' => '', 'name' => '', 'sig' => ''];

    private $emailRow;

    public function __construct($emailName, $langId) {
        $this->emailRow = $this->db->query(
            'select * from emails_list where email_name = :name and lang_id = :lid',
            [
                'name' => $emailName,
                'lid'  => $langId
            ]
        )->fetch();
    }

    public function send($memberId, array $dataVars = [], string $emailToSend = ''): int {
        $memberRow = $this->db->query(
            'SELECT member_email, member_nick FROM members WHERE member_id = :mid', ['mid' => $memberId]
        )->fetch();

        if (!$memberRow) {
            return 0;
        }

        $dataVars['memberNick'] = $memberRow['member_nick'];
        $dataVars['teamName']   = self::$emailFrom['sig'];
        $dataVars['siteName']   = $dataVars['siteName'] ?? $_SERVER['HTTP_HOST'];


        $mustache = new \Mustache_Engine();

        $emailRow = $this->emailRow;

        $title = $mustache->render($emailRow['email_title'], $dataVars);
        $text  = $mustache->render($emailRow['email_text'],  $dataVars);
        //$html  try { $mustache->render($emailRow['email_html'],  $dataVars);


        $mail = new PHPMailer();

        $mail->CharSet = 'UTF-8';

        $mail->setFrom(   self::$emailFrom['mail'],  self::$emailFrom['name'] );
        $mail->addReplyTo(self::$emailReply['mail'], self::$emailReply['name']);

        $mail->addAddress($emailToSend ? $emailToSend : $memberRow['member_email'], $memberRow['member_nick']);

        $mail->Subject = "$title";
        $mail->Body    = "$text";

        $this->db->query(
            'SELECT emails_add_log(:mid, :eid, :data)',
            ['mid' => $memberId, 'eid' => $this->emailRow['email_id'], 'data' => json_encode($dataVars)]
        );


        return $mail->send() ? 1 : 0;
    }

    public static function setEmailFrom($mail, $name, $signature) {
        self::$emailFrom = [
            'mail' => $mail,
            'name' => $name,
            'sig'  => $signature
        ];
    }

    public static function setEmailReply($mail, $name, $signature) {
        self::$emailReply = [
            'mail' => $mail,
            'name' => $name,
            'sig'  => $signature
        ];
    }

}