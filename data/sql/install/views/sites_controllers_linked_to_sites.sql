create or replace view sites_controllers_linked_to_sites  AS SELECT
  s.site_id,
  s.site_name,
  sc.controller_id,
  scs.controller_id AS controller_linked
FROM
  sites AS s
    CROSS JOIN sites_controllers AS sc
    LEFT JOIN sites_controllers_to_sites AS scs ON scs.site_id = s.site_id AND scs.controller_id = sc.controller_id
ORDER BY s.site_name ASC;