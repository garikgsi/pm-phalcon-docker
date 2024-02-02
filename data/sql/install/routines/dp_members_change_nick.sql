create function dp_members_change_nick(in_member_id bigint, in_nick varchar(100))
  returns integer
  language plpgsql
as $$
BEGIN
  IF NOT EXISTS(SELECT 1 FROM members WHERE member_id = in_member_id) THEN
    RETURN 1;
  END IF;

  IF EXISTS(SELECT 1 FROM members WHERE member_id <> in_member_id AND member_nick_lower = LOWER(in_nick)) THEN
    RETURN 2;
  END IF;

  UPDATE members SET member_nick = in_nick, member_nick_lower = LOWER(in_nick) WHERE member_id = in_member_id;

  RETURN 0;
END;
$$;