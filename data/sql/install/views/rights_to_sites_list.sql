create view rights_to_sites_list as
SELECT
  rts.site_id,
  r.right_id,
  r.right_in_use,
  r.right_name,
  COALESCE(r.right_parent_id, 0) AS parent_id,
  l.lang_id,
  rl.right_title,
  rl.right_description
FROM
  rights_to_sites AS rts,
  rights AS r
    CROSS JOIN languages AS l
    LEFT JOIN rights_langs AS rl ON l.lang_id = rl.lang_id AND rl.right_id = r.right_id
WHERE
    r.right_id = rts.right_id AND
    r.right_common = 0
ORDER BY r.right_order ASC;