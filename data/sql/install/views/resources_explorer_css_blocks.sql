create or replace view resources_explorer_css_blocks as
  SELECT r.resource_dir AS file_dir,
         r.resource_name AS file_name,
         2 AS file_block,
         ('Group: '::text || array_to_string(array_agg((rp.package_name)::character varying(512)), ', '::text)) AS block_name,
         array_to_string(array_agg(rp.package_id), ','::text) AS package_ids,
         'indigo'::text AS block_color,
         'package'::text AS block_type
  FROM resources r,
    resources_packages rp
  WHERE ((rp.package_id = r.package_id) AND (r.type_id = 1))
  GROUP BY r.resource_dir, r.resource_name, 2::integer, 'indigo'::text, 'package'::text
  UNION
  SELECT r.resource_dir AS file_dir,
         r.resource_name AS file_name,
         2 AS file_block,
         ('Site: '::text || array_to_string(array_agg((s.site_name)::character varying(128)), ', '::text)) AS block_name,
         array_to_string(array_agg(s.site_id), ','::text) AS package_ids,
         'blue'::text AS block_color,
         'site'::text AS block_type
  FROM resources r,
    resources_to_sites rts,
    sites s
  WHERE ((r.package_id IS NULL) AND (r.type_id = 1) AND (rts.resource_id = r.resource_id) AND (s.site_id = rts.site_id))
  GROUP BY r.resource_dir, r.resource_name, 2::integer, 'blue'::text, 'site'::text
  UNION
  SELECT r.resource_dir AS file_dir,
         r.resource_name AS file_name,
         2 AS file_block,
         ('App: '::text || array_to_string(array_agg((a.app_name)::character varying(128)), ', '::text)) AS block_name,
         array_to_string(array_agg(a.app_id), ','::text) AS package_ids,
         'green'::text AS block_color,
         'app'::text AS block_type
  FROM resources r,
    resources_to_apps rta,
    apps a
  WHERE ((r.package_id IS NULL) AND (r.type_id = 1) AND (rta.resource_id = r.resource_id) AND (a.app_id = rta.app_id))
  GROUP BY r.resource_dir, r.resource_name, 2::integer, 'green'::text, 'app'::text;