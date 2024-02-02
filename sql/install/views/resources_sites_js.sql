create or replace view resources_sites_js as
  SELECT scts.controller_id AS resource_id,
         s.site_id AS package_id,
         'js/site/'::text || (s.site_name)::text || '/' AS resource_dir,
         ((sc.controller_name)::text || '.js'::text) AS resource_name,
         2 AS type_id,
         'js'::text AS type_file_extension
  FROM sites s,
    sites_controllers sc,
    sites_controllers_to_sites scts
  WHERE ((scts.site_id = s.site_id) AND (sc.controller_id = scts.controller_id))
  UNION
  SELECT 0 AS resource_id,
         s.site_id AS package_id,
         'js/site/'::text AS resource_dir,
         ((s.site_name)::text || '.js'::text) AS resource_name,
         2 AS type_id,
         'js'::text AS type_file_extension
  FROM sites s
;

