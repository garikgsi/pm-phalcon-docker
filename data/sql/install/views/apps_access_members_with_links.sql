create view apps_access_members_with_links AS
SELECT
  m.member_id,
  m.member_nick,
  m.member_gender,
  aam.app_id
FROM
  members AS m,
  apps_access_members AS aam
WHERE
  m.member_id = aam.member_id
ORDER BY m.member_nick;





