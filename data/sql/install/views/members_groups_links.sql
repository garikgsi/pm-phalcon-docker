create view members_groups_links AS SELECT
  m.member_id,
  m.member_group AS group_id
FROM
  members as m

UNION

SELECT
  mg.member_id,
  mg.group_id
FROM
  members_groups AS mg;