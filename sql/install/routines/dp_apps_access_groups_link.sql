create or replace function dp_apps_access_groups_link(in_app_id integer, in_group_id integer) returns void
  language plpgsql
as $$
BEGIN
  IF NOT EXISTS(
      SELECT * FROM apps_access_groups AS acg
      WHERE acg.app_id = in_app_id AND acg.group_id = in_group_id
    ) THEN
    INSERT INTO apps_access_groups (app_id, group_id) VALUES (in_app_id, in_group_id);
  END IF;

END
$$
;