create view apps_access_groups_with_links AS
SELECT
  gl.lang_id,
  gl.group_id,
  gl.group_name,
  gl.group_title,
  a.app_id,
  (CASE WHEN aag.app_id IS NULL THEN 0 ELSE 1 END) AS app_linked
FROM
  groups_list AS gl
    CROSS JOIN apps AS a
    LEFT JOIN apps_access_groups AS aag ON a.app_id = aag.app_id AND aag.group_id = gl.group_id;





