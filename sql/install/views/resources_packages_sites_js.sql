create or replace view resources_packages_sites_js as
  SELECT s.site_id AS package_id,
         s.site_name AS package_name,
    rt.type_id,
    rt.type_name,
    rt.type_file_extension,
         count(scts.controller_id) AS num
  FROM resources_types rt,
    (sites s
      LEFT JOIN sites_controllers_to_sites scts ON ((scts.site_id = s.site_id)))
  WHERE (rt.type_id = 2)
  GROUP BY s.site_id, s.site_name, rt.type_id, rt.type_name, rt.type_file_extension
  ORDER BY s.site_name;

