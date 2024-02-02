create or replace view rights_granted_to_members AS
SELECT
  rlm.lang_id,
  m.member_id,
  m.member_nick,
  m.member_gender,
  m.member_email,
  m.member_group,
  rlm.right_id,
  rlm.right_name,
  rlm.right_in_use,
  rlm.right_title,
  rlm.right_description
FROM
  rights_list_complete AS rlm,
  members_rights AS mr,
  members AS m
WHERE
    mr.right_id = rlm.right_id AND
    m.member_id = mr.member_id
ORDER BY m.member_nick;