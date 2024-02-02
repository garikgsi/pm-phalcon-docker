create or replace view resources_system as
  SELECT r.resource_id,
    r.resource_dir,
    r.resource_name,
    r.package_id,
    r.resource_minified,
    rt.type_id,
    rt.type_file_extension
  FROM resources r,
    resources_types rt
  WHERE ((r.package_id IS NOT NULL) AND (rt.type_id = r.type_id))
  ORDER BY r.resource_order;

