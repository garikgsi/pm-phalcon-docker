create or replace view groups_list AS SELECT
  g.group_id,
  g.group_name,
  gl.group_title,
  l.lang_id,
  COALESCE(g.group_parent_id, 0) AS group_parent_id,
  g.group_system,
  g.group_leader_id
FROM
  languages AS l
    CROSS JOIN groups AS g
    LEFT JOIN groups_langs gl ON g.group_id = gl.group_id AND l.lang_id = gl.lang_id
ORDER BY g.group_id ASC
