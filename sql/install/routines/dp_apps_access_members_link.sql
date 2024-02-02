create function dp_apps_access_members_link(in_app_id integer, in_member_id bigint) returns void
  language plpgsql
as
$$
BEGIN
  IF NOT EXISTS(
      SELECT * FROM apps_access_members AS acm
      WHERE acm.app_id = in_app_id AND acm.member_id = in_member_id
    ) THEN
    INSERT INTO apps_access_members (app_id, member_id) VALUES (in_app_id, in_member_id);
  END IF;
END
$$;