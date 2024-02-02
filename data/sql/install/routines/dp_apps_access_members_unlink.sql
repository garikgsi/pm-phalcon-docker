create function dp_apps_access_members_unlink(in_app_id integer, in_member_id bigint) returns void
  language plpgsql
as
$$
BEGIN
  IF EXISTS(
      SELECT * FROM apps_access_members AS acm
      WHERE acm.app_id = in_app_id AND acm.member_id = in_member_id
    ) THEN
    DELETE FROM apps_access_members WHERE app_id = in_app_id AND member_id = in_member_id;
  END IF;
END
$$;