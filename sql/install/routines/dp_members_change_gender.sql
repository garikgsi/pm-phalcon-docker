create function dp_members_change_gender(in_member_id bigint, in_gender gender)
  returns integer
  language plpgsql
as $$
BEGIN
  IF NOT EXISTS(SELECT 1 FROM members WHERE member_id = in_member_id) THEN
    RETURN 1;
  END IF;

  UPDATE members SET member_gender = in_gender WHERE member_id = in_member_id;

  RETURN 0;
END;
$$;