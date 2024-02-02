create or replace function dp_sites_icon_delete(in_site_id integer, in_image_name varchar64) returns smallint
language plpgsql
as $$
BEGIN
  DELETE FROM sites_icons AS si WHERE si.site_id = in_site_id AND si.icon_name = LOWER(in_image_name);

  RETURN 0;
END;
$$
;

