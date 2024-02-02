create function dp_members_change_password(in_member_id bigint, in_salt varchar(64), in_hash hash_md5)
  returns integer
  language plpgsql
as $$
BEGIN
  IF NOT EXISTS(SELECT 1 FROM members WHERE member_id = in_member_id) THEN
    RETURN 1;
  END IF;

  UPDATE members_auth SET auth_hash = in_hash, auth_salt = in_salt WHERE member_id = in_member_id;

  RETURN 0;
END;
$$;