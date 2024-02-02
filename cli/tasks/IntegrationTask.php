<?php

use Phalcon\Cli\Task;

class IntegrationTask extends Task
{
    public function mainAction()
    {
        echo 'Это задача по умолчанию и действие по умолчанию' . PHP_EOL;
    }

    /**
     * @param $params
     * @cmd php cli.php integration membersFullSync
     */
    public function membersFullSyncAction()
    {
        $pmGroups = $this->db->query(
            '
                SELECT e.external_name
                     , eg.group_id
                     , eg.external_group_id
                FROM integration.external_group AS eg
                   , integration.external AS e
                WHERE e.external_id = eg.external_id
            '
        );

        $pmGroupsExternalMap = [];

        while($row = $pmGroups->fetch(PDO::FETCH_ASSOC)) {
            $pmGroupsExternalMap[$row['external_name']] = $pmGroupsExternalMap[$row['external_name']] ?? [];
            $pmGroupsExternalMap[$row['external_name']][$row['external_group_id']] = $row['group_id'];
        }

        $sdnGroups = $this->sdb->query(
            '
                SELECT g.group_id AS sdn_group_id
                     , g.group_name
                     , g.group_parent_id
                     , g.group_system
                     , g.group_leader_id
                     , g.group_assignable
                     , g.group_primary_only
                     , g.external_tts_id
                     , g.external_sd_id
                     , g.external_1c_id
                     , gl.group_title
                FROM public.groups AS g
                   , public.groups_langs AS gl
                WHERE gl.group_id = g.group_id
                  AND gl.lang_id = 1
            '
        );

        $sdnGroupsList = [];

        while($row = $sdnGroups->fetch()) {
            $sdnGroupsList[] = $row;

            if (isset($pmGroupsExternalMap['sdn'][$row['sdn_group_id']])) {
                continue;
            }

            $pmGroupId = (int)$this->db->query(
                'SELECT public.dp_groups_add(:name, :parent, :lang, :title) AS res',
                [
                    'name' => $row['group_name'],
                    'parent' => null,
                    'lang' => 1,
                    'title' => $row['group_title']

                ]
            )->fetch()['res'];

            $this->db->query(
                '
                    INSERT INTO integration.external_group(
                                external_id
                              , group_id  
                              , external_group_id  
                              )
                    VALUES(:eid
                         , :gid
                         , :egid  
                         )
                ',
                [
                    'eid' => 6,
                    'gid' => $pmGroupId,
                    'egid' => $row['sdn_group_id']
                ]
            );

            $pmGroupsExternalMap['sdn'][$row['sdn_group_id']] = $pmGroupId;
        }

        // ТЕПЕРЬ МОЖНО ДОБАВИТЬ ПОЛЬЗОВАТЕЛЕЙ. БЫТЬ МОЖЕТ ГРУППЫ НЕ ДОЗАПОЛНЕНЫ, НО ПОМЕСТИТЬ ПОЛЬЗАКОВ УЖЕ МОЖЕМ

        $pmMembers = $this->db->query(
            '
                SELECT e.external_name
                     , em.member_id
                     , em.external_member_id
                     , (CASE WHEN mg.group_id = 5 THEN 0 ELSE 1 END) AS is_active
                FROM integration.external_member AS em
                     LEFT JOIN public.members_groups AS mg ON mg.member_id = em.member_id AND mg.group_id = 5 
                   , integration.external AS e
                WHERE e.external_id = em.external_id
            '
        );

        $pmMembersExternalMap = [];
        $pmMembersStatus = [];

        while($row = $pmMembers->fetch(PDO::FETCH_ASSOC)) {
            $pmMembersExternalMap[$row['external_name']] = $pmMembersExternalMap[$row['external_name']] ?? [];
            $pmMembersExternalMap[$row['external_name']][$row['external_member_id']] = $row['member_id'];
            $pmMembersStatus[$row['member_id']] = $row['is_active'];
        }

        $sdnMembers = $this->sdb->query(
            '
                SELECT m.member_id AS sdn_member_id
                     , m.member_email
                     , m.member_nick
                     , m.member_nick_lower
                     , m.member_group  
                     , m.external_tts_id    
                     , m.external_sd_id     
                     , m.external_1c_id     
                     , m.external_tts_login 
                     , m.external_billing_id
                     , m.external_hydra_id  
                     , (CASE WHEN mg.group_id = 5 THEN 0 ELSE 1 END) AS is_active
                FROM public.members AS m
                     LEFT JOIN public.members_groups AS mg ON mg.member_id = m.member_id AND mg.group_id = 5
            '
        );

        $sdnMembersList = [];

        while($row = $sdnMembers->fetch()) {
            $sdnMembersList[] = $row;

            if (isset($pmMembersExternalMap['sdn'][$row['sdn_member_id']])) {
                continue;
            }

            $pmMemberId = (int)$this->db->query(
                '                
                    INSERT INTO public.members(
                                member_email
                              , member_nick
                              , member_nick_lower
                              , member_group
                    ) 
                    VALUES(:email
                         , :nick
                         , :nickl
                         , :mg                              
                    )     
                    RETURNING member_id
                ',
                [
                    'email' => $row['member_email'],
                    'nick' => $row['member_nick'],
                    'nickl' => $row['member_nick_lower'],
                    'mg' => $pmGroupsExternalMap['sdn'][$row['member_group']]
                ]
            )->fetch()['member_id'];

            $this->db->query(
                '
                    INSERT INTO integration.external_member(
                                external_id
                              , member_id  
                              , external_member_id  
                              )
                    VALUES(:eid
                         , :mid
                         , :emid  
                         )
                ',
                [
                    'eid' => 6,
                    'mid' => $pmMemberId,
                    'emid' => $row['sdn_member_id']
                ]
            );

            $this->db->query(
                '
                    INSERT INTO integration.auth_member(
                                auth_id
                              , member_id  
                              , auth_login  
                              )
                    VALUES(:eid
                         , :mid
                         , :log
                         )
                ',
                [
                    'eid' => 1,
                    'mid' => $pmMemberId,
                    'log' => $row['external_tts_login']
                ]
            );

            $pmMembersExternalMap['sdn'][$row['sdn_member_id']] = $pmMemberId;
        }

        for($a = 0, $len = sizeof($sdnMembersList); $a < $len; $a++) {
            $member = $sdnMembersList[$a];

            if (!isset($pmMembersExternalMap['sdn'][$member['sdn_member_id']])) {
                continue;
            }

            $pmMemberId = $pmMembersExternalMap['sdn'][$member['sdn_member_id']];

            if ($member['external_tts_id'] && !isset($pmMembersExternalMap['tts'][$member['external_tts_id']])) {
                $this->db->query(
                    '
                        INSERT INTO integration.external_member(external_id, member_id, external_member_id)
                        VALUES(:eid, :mid, :emid)
                    ',
                    ['eid' => 2, 'mid' => $pmMemberId, 'emid' => $member['external_tts_id']]
                );

                echo "tts_linked\n";
            }

            if ($member['external_sd_id'] && !isset($pmMembersExternalMap['sd'][$member['external_sd_id']])) {
                $this->db->query(
                    '
                        INSERT INTO integration.external_member(external_id, member_id, external_member_id)
                        VALUES(:eid, :mid, :emid)
                    ',
                    ['eid' => 1, 'mid' => $pmMemberId, 'emid' => $member['external_sd_id']]
                );

                echo "sd_linked\n";
            }

            if ($member['external_1c_id'] && !isset($pmMembersExternalMap['1c'][$member['external_1c_id']])) {
                $this->db->query(
                    '
                        INSERT INTO integration.external_member(external_id, member_id, external_member_id)
                        VALUES(:eid, :mid, :emid)
                    ',
                    ['eid' => 5, 'mid' => $pmMemberId, 'emid' => $member['external_1c_id']]
                );

                echo "1c_linked\n";
            }

            if ($member['external_billing_id'] && !isset($pmMembersExternalMap['mariann'][$member['external_billing_id']])) {
                $this->db->query(
                    '
                        INSERT INTO integration.external_member(external_id, member_id, external_member_id)
                        VALUES(:eid, :mid, :emid)
                    ',
                    ['eid' => 4, 'mid' => $pmMemberId, 'emid' => $member['external_billing_id']]
                );

                echo "mariann_linked\n";
            }

            if ($member['external_hydra_id'] && !isset($pmMembersExternalMap['hydra'][$member['external_hydra_id']])) {
                $this->db->query(
                    '
                        INSERT INTO integration.external_member(external_id, member_id, external_member_id)
                        VALUES(:eid, :mid, :emid)
                    ',
                    ['eid' => 3, 'mid' => $pmMemberId, 'emid' => $member['external_hydra_id']]
                );

                echo "hydra_linked\n";
            }

            if ($member['is_active'] != $pmMembersStatus[$pmMemberId]) {
                if ($member['is_active']) {
                    $sql = 'DELETE FROM public.members_groups WHERE member_id = :mid AND group_id = :gid';

                    echo "Member: ".$pmMemberId." [ACTIVATED]\n";
                }
                else {
                    $sql = 'INSERT INTO public.members_groups(member_id, group_id) VALUES(:mid, :gid)';

                    echo "Member: ".$pmMemberId." [DEACTIVATED]\n";
                }
                $this->db->query($sql, ['gid' => 5, 'mid' => $pmMemberId]);
            }
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        for($a = 0, $len = sizeof($sdnGroupsList); $a < $len; $a++) {
            $group = $sdnGroupsList[$a];

            if (!isset($pmGroupsExternalMap['sdn'][$group['sdn_group_id']])) {
                continue;
            }

            $pmGroupId = $pmGroupsExternalMap['sdn'][$group['sdn_group_id']];

            if ($group['external_tts_id'] && !isset($pmGroupsExternalMap['tts'][$group['external_tts_id']])) {
                $this->db->query(
                    '
                    INSERT INTO integration.external_group(external_id, group_id, external_group_id)
                    VALUES(:eid, :gid, :egid)
                ',
                    [
                        'eid' => 2,
                        'gid' => $pmGroupId,
                        'egid' => $group['external_tts_id']
                    ]
                );

                echo "tts_linked\n";
            }

            if ($group['external_sd_id'] && !isset($pmGroupsExternalMap['sd'][$group['external_sd_id']])) {
                $this->db->query(
                    '
                    INSERT INTO integration.external_group(external_id, group_id, external_group_id)
                    VALUES(:eid, :gid, :egid)
                ',
                    [
                        'eid' => 1,
                        'gid' => $pmGroupId,
                        'egid' => $group['external_sd_id']
                    ]
                );

                echo "sd_linked\n";
            }

            if ($group['external_1c_id'] && !isset($pmGroupsExternalMap['1c'][$group['external_1c_id']])) {
                $this->db->query(
                    '
                    INSERT INTO integration.external_group(external_id, group_id, external_group_id)
                    VALUES(:eid, :gid, :egid)
                ',
                    [
                        'eid' => 5,
                        'gid' => $pmGroupId,
                        'egid' => $group['external_1c_id']
                    ]
                );

                echo "1c_linked\n";
            }

            $this->db->query(
                '
                    SELECT public.dp_groups_change_parent(:gid, :pid)
                ',
                [
                    'gid' => $pmGroupId,
                    'pid' => $pmGroupsExternalMap['sdn'][$group['group_parent_id']] ?? null
                ]
            );


        }
    }
}