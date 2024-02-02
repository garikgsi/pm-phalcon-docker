create or replace function dp_apps_access_sites_unlink(in_app_id integer, in_site_id integer) returns void
  language plpgsql
as $$
BEGIN
  IF EXISTS(
      SELECT * FROM apps_access_sites AS acs
      WHERE acs.app_id = in_app_id AND acs.site_id = in_site_id
    ) THEN
    DELETE FROM apps_access_sites WHERE app_id = in_app_id AND site_id = in_site_id;
  END IF;
END
$$
;