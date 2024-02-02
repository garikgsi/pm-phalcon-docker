CREATE FUNCTION dp_members_register(in_email email, in_gender varchar(10), in_nick varchar(100), in_date date, in_lang integer, in_salt varchar(64), in_hash hash_md5, in_activation_key varchar(32))
  RETURNS bigint
LANGUAGE plpgsql
AS $$
DECLARE
  new_member_id integer;
BEGIN
  IF (EXISTS(SELECT 1 FROM members WHERE member_nick_lower = LOWER(in_nick))) THEN
    return -1;
  END IF;


  IF (EXISTS(SELECT 1 FROM members WHERE member_email = LOWER(in_email))) THEN
    return -2;
  END IF;

  INSERT INTO members (
      member_email,
      member_gender,
      member_nick,
      member_nick_lower,
      member_date_birth,
      lang_id,
      member_group
      )
  VALUES (
             in_email,
             in_gender,
             in_nick,
             LOWER(in_nick),
             in_date,
             in_lang,
             4 -- Добавляем в группу ожидающих
             );

  new_member_id = currval('members_member_id_seq');

  INSERT INTO members_auth (member_id, auth_salt, auth_hash) VALUES (new_member_id, in_salt, in_hash);
  INSERT INTO members_keys (member_id, key_type, key_value) VALUES (new_member_id, 'activation', in_activation_key);

  RETURN new_member_id;
END;
$$;

