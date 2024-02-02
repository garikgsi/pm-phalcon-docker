create function dp_members_activation(in_activation_key character varying)
  returns smallint
language plpgsql
as $$
DECLARE
  tmp_member_id bigint;
BEGIN
  SELECT member_id FROM members_keys WHERE key_value = in_activation_key AND key_type = 'activation' INTO tmp_member_id;

  IF (tmp_member_id IS NULL) THEN
    return 1;
  END IF;

  UPDATE members SET member_group = 2 WHERE member_id = tmp_member_id;

  DELETE FROM members_keys WHERE member_id = tmp_member_id AND key_type = 'activation';


  RETURN 0;
END;
$$;