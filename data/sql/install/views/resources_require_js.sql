create or replace view resources_require_js as
  SELECT r.resource_id,
    r.resource_dir,
    r.resource_name,
    0 AS package_id,
    r.resource_minified,
    2 AS type_id,
    'js' AS type_file_extension
  FROM resources r
  WHERE ((r.package_id IS NULL) AND (r.type_id = 2))
  ORDER BY r.resource_dir, r.resource_name;

