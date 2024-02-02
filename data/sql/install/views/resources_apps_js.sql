create or replace view resources_apps_js as
  SELECT 0 AS resource_id,
         'js/app/' AS resource_dir,
         ((a.app_name)::text || '.js'::text) AS resource_name,
         0 AS package_id,
         0 AS resource_minified,
         2 AS type_id,
         'js' AS type_file_extension
  FROM apps a
  ORDER BY a.app_name;

