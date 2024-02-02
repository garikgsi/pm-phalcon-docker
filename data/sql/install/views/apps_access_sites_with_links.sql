create view apps_access_sites_with_links AS
SELECT
  s.site_id,
  s.site_name,
  a.app_id,
  (CASE WHEN aas.app_id IS NULL THEN 0 ELSE 1 END) AS app_linked
FROM
  apps AS a
  CROSS JOIN sites AS s
  LEFT JOIN apps_access_sites AS aas ON a.app_id = aas.app_id AND aas.site_id = s.site_id
ORDER BY s.site_name;





