create or replace view resources_packages_system as
  SELECT rp.package_id,
    rp.package_name,
    rp.package_end,
    rp.type_id,
    rt.type_name,
    rt.type_file_extension
  FROM resources_packages rp,
    resources_types rt
  WHERE rt.type_id = rp.type_id
  ORDER BY rp.package_end, rp.package_order;

