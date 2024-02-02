create or replace view resources_sites_css as
  SELECT
    r.resource_id,
    r.resource_dir,
    r.resource_name,
    rts.site_id,
    r.resource_minified,
    rt.type_id,
    rt.type_file_extension
  FROM
    resources AS r,
    resources_types AS rt,
    resources_to_sites AS rts
  WHERE
    r.package_id IS NULL AND
    r.type_id = 1 AND
    rt.type_id = r.type_id AND
    rts.resource_id = r.resource_id
  ORDER BY
    r.resource_order;