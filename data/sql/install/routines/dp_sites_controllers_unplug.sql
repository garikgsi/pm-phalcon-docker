create or replace function dp_sites_controllers_unplug(in_site_id integer, in_controller_id integer) returns void
language plpgsql
as $$
BEGIN
  IF EXISTS(
      SELECT * FROM sites_controllers_to_sites AS scts
      WHERE scts.site_id = in_site_id AND scts.controller_id = in_controller_id
  ) THEN
    DELETE FROM sites_controllers_to_sites WHERE site_id = in_site_id AND controller_id = in_controller_id;
  END IF;
END
$$
;

