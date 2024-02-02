<?php
namespace Api\Utils;


use Core\Extender\ControllerSender;

class ImportController extends ControllerSender
{
    private $groupsEvaToIphinoe = [
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 6,
        6 => 2,
        7 => 7,
        8 => 2,
        9 => 2,
        10 => 2,
        11 => 2,
        12 => 5,
        13 => 2,
        14 => 2,
        15 => 2,
        16 => 2

    ];

    public function initialize() {

    }

    public function importMembers() {
        $mid = $this->db->query('select min(member_id) AS mid from members')->fetch()['mid'];

        $members = $this->mdb->query(
            '
                SELECT 
                    `sm`.`member_id`,
                    `sm`.`member_email`,
                    `sm`.`member_gender`,
                    `sm`.`member_mgroup`,
                    `sm`.`member_nick_original`,
                    `sm`.`member_date_birth`,
                    `sm`.`member_date_registrated`,
                    `sma`.`auth_salt`,
                    `sma`.`auth_hash`
                FROM 
                    `scl_members` AS `sm`,
                    `scl_members_auth` AS `sma`
                WHERE
                    `sma`.`member_id` = `sm`.`member_id` AND 
                    `sm`.`member_id` < '.$mid.' 
                ORDER BY `sm`.`member_id` ASC    
            '
        );

        echo '<pre>';

        while($row = $members->fetch()) {
            print_r($row);

            $this->db->query('
                    SELECT dp_sync_member_insert(
                        :id,
                        :gender,
                        :group,
                        :email,
                        :nick,
                        :date_register,                        
                        :date_birth::date,
                        :salt,
                        :hash
                    )
                ',
                [
                    'id'     => (int)$row['member_id'],
                    'gender' => $row['member_gender'],
                    'group'  => $this->groupsEvaToIphinoe[(int)$row['member_mgroup']],
                    'email'  => $row['member_email'],
                    'nick'   => $row['member_nick_original'],
                    'date_register' => $row['member_date_registrated'] ? $row['member_date_registrated'] : '1990-02-17 00:00:00',
                    'date_birth'    => $row['member_date_birth'] ? $row['member_date_birth'] : '1990-02-17',
                    'salt' => $row['auth_salt'],
                    'hash' => $row['auth_hash']
                ]
            );
        }
    }





}