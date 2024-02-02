create function dp_members_activation_mail_change_swap(in_member_id bigint, in_new_email email, in_salt character varying, in_hash hash_md5)
  returns smallint
  language plpgsql
as $$
BEGIN
  -- нет неактивированного пользователя
  IF (NOT EXISTS(SELECT 1 FROM members WHERE member_group = 4 AND member_id = in_member_id)) THEN
    return 1;
  END IF;

  -- по какой-то причине такой имейл заняли уже \0/
  IF (EXISTS(SELECT 1 FROM members WHERE member_email = in_new_email)) THEN
    return 2;
  END IF;

  -- При активации надо удалить ключи на активацию и смену имейла, а также запись под саму смену имейла
  DELETE FROM members_keys WHERE member_id = in_member_id AND key_type IN ('activation', 'activatechange');
  DELETE FROM members_change_email WHERE member_id = in_member_id;

  UPDATE members
  SET
    member_email = in_new_email,
    member_group = 2
  WHERE member_id = in_member_id;

  UPDATE members_auth
  SET
    auth_salt = in_salt,
    auth_hash = in_hash
  WHERE member_id = in_member_id;

  RETURN 0;
END;
$$;