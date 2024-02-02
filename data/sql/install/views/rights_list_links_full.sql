create view rights_list_links_full AS SELECT
  rlc.*,
  grc.groups_count,
  mrc.members_count
FROM
  rights_list_complete AS rlc
    LEFT JOIN (
    SELECT COUNT(*) AS groups_count, gr.right_id FROM groups_rights AS gr GROUP BY gr.right_id
  ) AS grc ON grc.right_id = rlc.right_id
    LEFT JOIN (
    SELECT COUNT(*) AS members_count, mr.right_id FROM members_rights AS mr GROUP BY mr.right_id
  ) AS mrc ON mrc.right_id = rlc.right_id