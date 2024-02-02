create function dp_members_delete(in_member_id bigint)
  returns smallint
  language plpgsql
as $$
BEGIN
  DELETE FROM emails_log WHERE member_id = in_member_id;

  DELETE FROM members_log_auth  WHERE member_id = in_member_id;
  DELETE FROM members_log_email WHERE member_id = in_member_id;
  DELETE FROM members_log_nick  WHERE member_id = in_member_id;
  DELETE FROM members_cookies   WHERE member_id = in_member_id;
  DELETE FROM members_auth      WHERE member_id = in_member_id;
  DELETE FROM members_keys      WHERE member_id = in_member_id;
  DELETE FROM members           WHERE member_id = in_member_id;

  RETURN 0;
END;
$$;