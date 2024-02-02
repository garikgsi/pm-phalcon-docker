<?php
namespace Core\Driver;


use Phalcon\DI;

class MemberSql
{
    const SQL = 'db';

    public static function dbCookieInsert($memberId, $cookieKey) {
        DI::getDefault()[self::SQL]->query(
            'INSERT INTO members_cookies (member_id, cookie_key) VALUES(:mid, :key)',
            [
                'mid' => $memberId,
                'key' => $cookieKey
            ]
        );
    }

    public static function dbCookieSelect($cookieKey) {
        return (int)DI::getDefault()[self::SQL]->query(
            'SELECT dp_members_get_by_cookie(:key) AS member_id', ['key' => $cookieKey]
        )->fetch()['member_id'];
    }

    public static function dbCookieDelete($cookieKey) {
        DI::getDefault()[self::SQL]->query('delete from members_cookies where cookie_key = :key', ['key' => $cookieKey]);
    }

    public static function dbMemberSelect($memberId) {
        $memberRow = DI::getDefault()[self::SQL]->query(
            '
                SELECT 
                    m.member_id AS id,
                    m.member_nick AS nick,
                    m.member_gender AS gender,
                    m.member_date_birth AS age,
                    m.member_group AS "group",
                    m.member_email AS email,
                    COALESCE(ma.avatar_ver, 0) AS avatar,
                    COALESCE(ma.avatar_type, \'\') AS avatar_type
                FROM
                    members AS m
                    LEFT JOIN members_avatars AS ma ON ma.member_id = m.member_id
                WHERE  
                    m.member_id = :mid 
                    
            ',
            ['mid' => $memberId]
        )->fetch();

        $groups = DI::getDefault()[self::SQL]->query(
            '
                SELECT 
                    group_id
                FROM
                    members_groups
                WHERE  
                    member_id = :mid  
            ',
            ['mid' => $memberId]
        );

        $groupsList = [$memberRow['group']];

        while($row = $groups->fetch()) {
            $groupsList[] = $row['group_id'];
        }

        $memberRow['groups'] = $groupsList;
        $memberRow['rights'] = self::dbMemberRights($memberId);
        $memberRow['access'] = self::dbMemberAccess($memberId, (int)$memberRow['group'], $groupsList);
        //$memberRow['rpgi']   = self::dbMemberRPGIData($memberId);

        return $memberRow;
    }



    public static function dbMemberRights($memberId) {
        if ($memberId == 0) {
            return [];
        }

        $rights = DI::getDefault()['db']->query('SELECT * FROM public.dp_members_rights_links_list(:mid::bigint, 1)', ['mid' => $memberId]);

        $rightsList = [];

        while($row = $rights->fetch()) {
            if ((int)$row['given_by_member']) {
                $rightsList[$row['right_name']] = 1;
            }
            else if ((int)$row['given_by_group']) {
                if ((int)$row['is_active'] || (int)$row['is_inherit']) {
                    $rightsList[$row['right_name']] = 1;
                }
                else if((int)$row['is_group_leader'] && (int)$row['right_given_to_leader']) {
                    $rightsList[$row['right_name']] = 1;
                }
            }

        }

        return $rightsList;
    }

    public static function dbMemberAccess($userId, $userGroupId, $userGroupsList) {
        $db = DI::getDefault()['db'];
        $NOT_ACTIVATED = [
            3 => true,
            4 => true
        ];


        if (isset($NOT_ACTIVATED[$userGroupId]) || $userId == 0) {
            $apps = $db->query(
                '
                    SELECT
                        *
                    FROM
                        public.apps
                    WHERE
                        app_access = :mask    
                ',
                ['mask' => '000']
            );
        }
        else {
            $apps = $db->query(
                '
                    SELECT
                        a1.app_name
                    FROM
                        public.apps AS a1
                    WHERE
                        a1.app_access = \'100\' OR 
                        a1.app_access = \'000\'
                    UNION 
                    -- Ограничение по группам
                    SELECT
                        a2.app_name
                    FROM
                        public.apps AS a2,
                        public.apps_access_groups AS aag
                    WHERE
                        a2.app_access LIKE \'11_\' AND
                        a2.app_id = aag.app_id AND
                        aag.group_id IN ('.implode(',', $userGroupsList).')
                    UNION
                    -- Ограничение по по пользователям
                    SELECT
                        a3.app_name
                    FROM
                        public.apps AS a3,
                        public.apps_access_members AS aam
                    WHERE
                        a3.app_access LIKE \'1_1\' AND
                        a3.app_id = aam.app_id AND
                        aam.member_id = :mid  
                ',
                ['mid' => $userId]
            );
        }

        $accessList = [];

        while($row = $apps->fetch()) {
            $accessList[$row['app_name']] = 1;
        }

        return $accessList;
    }

    /*public static function dbMemberRPGIData($memberId) {
        $db = DI::getDefault()['db'];

        $counters = $db->query(
            '
                SELECT uact.type_name
                     , \'public\' AS scope_name
                     , uacp.counter_value
                FROM rpginferno.usr_asset_counter_public AS uacp
                   , rpginferno.usr_asset_counter_type AS uact
                WHERE uact.asset_counter_type_id = uacp.asset_counter_type_id
                UNION
                SELECT uact2.type_name
                     , uacs.scope_name
                     , COALESCE(uac.counter_value, 0) AS counter_value
                FROM rpginferno.usr_asset_counter_type AS uact2
                     CROSS JOIN rpginferno.usr_asset_counter_scope AS uacs
                     LEFT JOIN rpginferno.usr_asset_counter AS uac
                         ON uact2.asset_counter_type_id = uac.asset_counter_type_id
                        AND uacs.asset_counter_scope_id = uac.asset_counter_scope_id
                        AND uac.member_id = :mid            
            ',
            [
                'mid' => $memberId
            ]
        );

        $countersData = [];

        while($row = $counters->fetch()) {
            $countersData[$row['type_name']] = $countersData[$row['type_name']] ?? [];
            $countersData[$row['type_name']][$row['scope_name']] = (int)$row['counter_value'];
        }

        return [
            'counters' => $countersData
        ];
    }*/
}
