create or replace function dp_apps_access_groups_unlink(in_app_id integer, in_group_id integer) returns void
  language plpgsql
as $$
BEGIN
  IF EXISTS(
      SELECT * FROM apps_access_groups AS acg
      WHERE acg.app_id = in_app_id AND acg.group_id = in_group_id
    ) THEN
    DELETE FROM apps_access_groups WHERE app_id = in_app_id AND group_id = in_group_id;
  END IF;
END
$$
;