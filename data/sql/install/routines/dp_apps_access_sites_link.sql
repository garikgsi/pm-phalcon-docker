create or replace function dp_apps_access_sites_link(in_app_id integer, in_site_id integer) returns void
  language plpgsql
as $$
BEGIN
  IF NOT EXISTS(
      SELECT * FROM apps_access_sites AS acs
      WHERE acs.app_id = in_app_id AND acs.site_id = in_site_id
    ) THEN
    INSERT INTO apps_access_sites (app_id, site_id) VALUES (in_app_id, in_site_id);
  END IF;
END
$$
;