create view rights_list_complete AS SELECT
  aal.lang_id,
  aal.app_id AS object_id,
  aal.app_name AS object_name,
  aal.app_title AS object_title,
  'app' AS object_type,
  rtal.parent_id,
  rtal.right_id,
  rtal.right_in_use,
  rtal.right_name,
  rtal.right_title,
  rtal.right_description
FROM
  rights_to_apps_list AS rtal,
  apps_list AS aal
WHERE
    aal.app_id = rtal.app_id AND
    aal.lang_id = rtal.lang_id

UNION

SELECT
  rtsl.lang_id,
  ssl.site_id AS object_id,
  ssl.site_name AS object_name,
  ssl.site_name AS object_title,
  'site' AS object_type,
  rtsl.parent_id,
  rtsl.right_id,
  rtsl.right_in_use,
  rtsl.right_name,
  rtsl.right_title,
  rtsl.right_description
FROM
  rights_to_sites_list AS rtsl,
  sites_list AS ssl
WHERE
    ssl.site_id = rtsl.site_id

UNION

SELECT
  scl.lang_id,
  scl.controller_id AS object_id,
  scl.controller_name AS object_name,
  scl.controller_title AS object_title,
  'controller' AS object_type,
  rtcl.parent_id,
  rtcl.right_id,
  rtcl.right_in_use,
  rtcl.right_name,
  rtcl.right_title,
  rtcl.right_description
FROM
  rights_to_controllers_list AS rtcl,
  sites_controllers_list AS scl
WHERE
    scl.controller_id = rtcl.controller_id AND
    scl.lang_id = rtcl.lang_id
UNION
SELECT
  rtnl.lang_id,
  0 AS object_id,
  'empty' AS object_name,
  '' AS object_title,
  'none' AS object_type,
  rtnl.parent_id,
  rtnl.right_id,
  rtnl.right_in_use,
  rtnl.right_name,
  rtnl.right_title,
  rtnl.right_description
FROM
  rights_to_noon_list AS rtnl;
