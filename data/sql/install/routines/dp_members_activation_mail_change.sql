create function dp_members_activation_mail_change(in_member_id bigint, in_email email, in_activation_key character varying)
  returns smallint
  language plpgsql
as $$
BEGIN
  -- нет неактивированного пользователя
  IF (NOT EXISTS(SELECT 1 FROM members WHERE member_group = 4 AND member_id = in_member_id)) THEN
    return 1;
  END IF;


  -- При активации надо удалить ключи на активацию и смену имейла, а также запись под саму смену имейла
  DELETE FROM members_keys WHERE member_id = in_member_id AND key_type = 'activatechange';
  DELETE FROM members_change_email WHERE member_id = in_member_id;

  INSERT INTO members_keys (
    member_id,
    key_type,
    key_value
  )
  VALUES (
           in_member_id,
           'activatechange',
           in_activation_key
         );

  INSERT INTO members_change_email (
    member_id,
    new_email
  )
  VALUES (
           in_member_id,
           in_email
         );

  RETURN 0;
END;
$$;