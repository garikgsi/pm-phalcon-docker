create function dp_members_change_password_and_email(in_member_id bigint, in_salt varchar(64), in_hash hash_md5, in_email email)
  returns integer
  language plpgsql
as $$
declare
  tmp_id bigint;
  tmp_email email;
BEGIN
  in_email = LOWER(in_email);

  SELECT member_id, LOWER(member_email) FROM members WHERE member_id = in_member_id INTO tmp_id, tmp_email;

  IF (tmp_id IS NULL) THEN
    RETURN 1;
  END IF;

  -- Если имейлы равны, то мы просто меняем пароль
  IF (tmp_email = in_email) THEN
    PERFORM dp_members_change_password(in_member_id, in_salt, in_hash);

    RETURN 0;
  END IF;

  IF EXISTS(SELECT 1 FROM members WHERE member_email = in_email) THEN
    RETURN 2;
  END IF;

  UPDATE members SET member_email = in_email WHERE member_id = in_member_id;
  PERFORM dp_members_change_password(in_member_id, in_salt, in_hash);

  RETURN 0;
END;
$$;