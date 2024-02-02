create or replace view resources_explorer_js_blocks as
  SELECT 'js/app/'::character varying AS file_dir,
         ((a.app_name)::text || '.js'::text) AS file_name,
         1 AS file_block,
         ('App: '::text || (a.app_name)::text) AS block_name,
         a.app_id::varchar256 AS package_ids,
         'purple'::text AS block_color
  FROM apps a
  UNION
  SELECT
    r.resource_dir AS file_dir,
    r.resource_name AS file_name,
    2 AS file_block,
    ('Group: '::text || array_to_string(array_agg(CAST(rp.package_name AS VARCHAR(128))),', ')) AS block_name,
    array_to_string(array_agg(rp.package_id),',') AS package_ids,
    'indigo'::text AS block_color
  FROM resources r,
    resources_packages rp
  WHERE ((rp.package_id = r.package_id) AND (r.type_id = 2))
  GROUP BY file_dir, file_name, file_block, block_color
  UNION
  SELECT 'js/site/'::character varying AS file_dir,
         ((s.site_name)::text || '.js'::text) AS file_name,
         1 AS file_block,
         ('Site: '::text || (s.site_name)::text) AS block_name,
         s.site_id::varchar256 AS package_ids,
         'blue'::text AS block_color
  FROM sites s
  UNION
  SELECT (('js/site/'::text || (s.site_name)::text) || '/'::text) AS file_dir,
         '*.js'::text AS file_name,
         1 AS file_block,
         ('Controller: '::text || (s.site_name)::text) AS block_name,
         '0' AS package_ids,
         'teal'::text AS block_color
  FROM sites s
  UNION
  SELECT r.resource_dir AS file_dir,
         r.resource_name AS file_name,
         1 AS file_block,
         'Require lib'::text AS block_name,
         '0' AS package_ids,
         'green'::text AS block_color
  FROM resources r
  WHERE ((r.package_id IS NULL) AND (r.type_id = 2));
