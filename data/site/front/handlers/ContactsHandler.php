<?php

namespace Site\Front\Handlers;


use Phalcon\Di\Injectable;

class ContactsHandler extends Injectable {
    private int $contactNodeId;



    public function __construct(int $contactNodeId)
    {
        $this->contactNodeId = $contactNodeId;
    }

    public function getContact(int $contactId)
    {
        $contact = $this->getContacts(['filter_contact_id' => $contactId])[0] ?? false;

        if (!$contact) {
            return false;
        }

        $signFile = $this->db->query(
            '
                SELECT * 
                FROM common.file AS f
                   , common.file_trait_file AS ftf
                WHERE f.file_node_id = :nid
                  AND ftf.file_id = f.file_id
                  AND ftf.file_trait_id = 1
            ',
            [
                'nid' => $contact['file_node_id']
            ]
        )->fetch();

        $contact['sign_file'] = !$signFile ? false : $signFile;

        $contact['info_list'] = $this->db->query(
            '
                SELECT ci.contact_info_id
                     , ci.contact_info_type_id
                     , ci.info_value
                     , ci.info_value_extra
                     , cit.type_title
                     , cit.type_input
                FROM pm.contact_info AS ci
                   , pm.contact_info_type AS cit
                WHERE ci.contact_id = :cid
                  AND cit.contact_info_type_id = ci.contact_info_type_id
                ORDER BY cit.type_order
                       , ci.contact_info_id
            ',
            [
                'cid' => $contactId
            ]
        )->fetchAll();

        $contact['work_days'] = $this->db->query(
            '
                SELECT cwd.day_number
                FROM pm.contact_work_day AS cwd
                WHERE cwd.contact_id = :cid
                ORDER BY day_number
            ',
            [
                'cid' => $contactId
            ]
        )->fetchAll();

        return $contact;
    }

    public function getContacts(array $options = []) : array
    {
        $stmtFrom  = [];
        $stmtWhere = [];

        if ($options['filter_node_traits'] ?? 0) {
            $stmtFrom[]  = 'pm.contact_trait_node_contact AS ctnc';

            $where  = '(';
            $where .= ' ctnc.contact_node_id = :nid';
            $where .= ' AND ctnc.contact_trait_id IN ('.implode(',', $options['filter_node_traits']).')';
            $where .= ' AND c.contact_id = ctnc.contact_id';
            $where .= ')';

            $stmtWhere[] = $where;
        }

        $stmtContactFrom  = [];
        $stmtContactWhere = [];

        if ($options['filter_contact_id'] ?? 0) {
            $stmtContactWhere[] = 'c.contact_id = '. (int)$options['filter_contact_id'];
        }


        $contacts = $this->db->query(
            '
                WITH contacts AS (
                     SELECT cnc.contact_node_id
                          , c.contact_id
                          , c.file_node_id
                          , c.contact_active
                          , c.contact_type_id
                          , c.contact_role_id
                          , c.contact_nickname
                          , c.contact_comment
                          , cr.role_title
                          , ct.type_name
                          , ct.type_order
                          , cp.personal_name
                          , cp.personal_surname
                          , cp.personal_patronymic
                          , cp.personal_birth_date
                          , cpg.contact_personal_gender_id
                          , cpg.gender_title
                          , cpg.gender_name
                          , to_char(cw.work_time_begin::time, \'HH24:MI\') AS work_time_begin
                          , to_char(cw.work_time_end::time, \'HH24:MI\') AS work_time_end
                     FROM pm.contact_node_contact AS cnc
                        , pm.contact AS c
                          LEFT JOIN pm.contact_personal AS cp ON cp.contact_id = c.contact_id
                          LEFT JOIN pm.contact_personal_gender AS cpg ON cpg.contact_personal_gender_id = cp.contact_personal_gender_id
                          LEFT JOIN pm.contact_work AS cw ON cw.contact_id = c.contact_id
                        , pm.contact_role AS cr
                        , pm.contact_type AS ct
                     WHERE cnc.contact_node_id = :nid
                       AND c.contact_id = cnc.contact_id
                       AND c.contact_hide = 0
                       AND cr.contact_role_id = c.contact_role_id
                       AND ct.contact_type_id = c.contact_type_id
                       '.(sizeof($stmtContactWhere) ? ' AND '.implode(' AND ', $stmtContactWhere) : '').'
                     )
                   , infos AS (
                     SELECT res.contact_id
                          , json_agg(json_build_object(
                                \'tid\', res.contact_info_type_id, 
                                \'val\', res.info_value, 
                                \'extra\', res.info_value_extra
                            ) ORDER BY res.contact_info_type_id) AS contact_info
                     FROM (
                             SELECT ci.contact_id
                                  , ci.contact_info_type_id
                                  , FIRST_VALUE(ci.info_value)       OVER(PARTITION BY ci.contact_id, ci.contact_info_type_id ORDER BY ci.contact_info_id) AS info_value
                                  , FIRST_VALUE(ci.info_value_extra) OVER(PARTITION BY ci.contact_id, ci.contact_info_type_id ORDER BY ci.contact_info_id) AS info_value_extra
                             FROM pm.contact_info AS ci
                             WHERE ci.contact_id IN (SELECT c.contact_id FROM contacts AS c)
                               AND ci.contact_info_type_id IN (1, 2, 4)
                          ) AS res
                     GROUP BY res.contact_id
                     )
                   , traits AS (
                     SELECT res.contact_id
                          , json_object_agg(res.contact_trait_id, res.contact_trait_id) AS contact_trait
                     FROM (
                             SELECT ctc.contact_id
                                  , ctc.contact_trait_id
                             FROM pm.contact_trait_contact AS ctc
                             WHERE ctc.contact_id IN (SELECT c.contact_id FROM contacts AS c)
                             UNION
                             SELECT ctnc.contact_id
                                  , ctnc.contact_trait_id
                             FROM pm.contact_trait_node_contact AS ctnc
                             WHERE ctnc.contact_node_id = :nid
                               AND ctnc.contact_id IN (SELECT c.contact_id FROM contacts AS c)
                          ) AS res
                     GROUP BY res.contact_id
                     )
                SELECT c.contact_node_id
                     , c.contact_id
                     , c.file_node_id
                     , c.contact_active
                     , c.contact_type_id
                     , c.contact_role_id
                     , c.contact_nickname
                     , c.contact_comment
                     , c.role_title
                     , c.type_name
                     , c.personal_name
                     , c.personal_surname
                     , c.personal_patronymic
                     , c.personal_birth_date
                     , c.contact_personal_gender_id
                     , c.gender_title
                     , c.gender_name
                     , c.work_time_begin
                     , c.work_time_end
                     , i.contact_info
                     , t.contact_trait
                     , ca.counteragent_id
                     , ca.counteragent_title
                FROM contacts AS c
                     LEFT JOIN infos  AS i ON i.contact_id = c.contact_id
                     LEFT JOIN traits AS t ON t.contact_id = c.contact_id
                   , pm.contact_node_contact AS cnc
                   , pm.counteragent AS ca  
                   '.(sizeof($stmtFrom) ? ','.implode(',', $stmtFrom) : '').'
                WHERE cnc.contact_id = c.contact_id
                  AND ca.contact_node_id = cnc.contact_node_id
                  '.(sizeof($stmtWhere) ? 'AND '.implode(' AND ', $stmtWhere) : '').'
                ORDER BY c.type_order
                       , c.contact_nickname
            ',
            [
                'nid' => $this->contactNodeId
            ]
        );

        $contactsList = [];

        while($row = $contacts->fetch()) {
            $nickShort = mb_substr(preg_replace('/[^A-ZА-Я]/mu', '', $row['contact_nickname']), 0, 2);
            $nickExplode = explode(' ', $row['contact_nickname']);
            $explodeSize = sizeof($nickExplode);

            if(mb_strlen($nickShort) < 2) {
                if ($explodeSize >= 2) {
                    $nickShort = mb_strtoupper(mb_substr($nickExplode[0], 0, 1).mb_substr($nickExplode[1], 0, 1));
                }
                else {
                    $nickShort = mb_strtoupper(mb_substr(preg_replace('/[^a-zа-я]/mu', '', $row['contact_nickname']), 0, 2));
                }
            }

            $row['contact_nickname_short'] = $nickShort;

            $contactsList[] = $row;
        }

        return $contactsList;
    }
}