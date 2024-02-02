create or replace view members_list_with_group_lang as
SELECT gl.lang_id,
       gl.group_id,
       gl.group_title,
       m.member_id,
       m.lang_id                                                                             AS member_lang,
       m.member_nick,
       m.member_group,
       m.member_gender,
       m.member_email,
       to_char(m.member_date_register, 'DD.MM.YYYY HH:MM:SS'::text)                          AS member_date_register,
       to_char(m.member_date_activity, 'DD.MM.YYYY HH:MM:SS'::text)                          AS member_date_activity,
       to_char((m.member_date_birth)::timestamp with time zone, 'DD.MM.YYYY HH:MM:SS'::text) AS member_date_birth,
       m.member_nick_lower
FROM members m,
     groups_list gl
WHERE (gl.group_id = m.member_group)
ORDER BY m.member_date_register DESC;