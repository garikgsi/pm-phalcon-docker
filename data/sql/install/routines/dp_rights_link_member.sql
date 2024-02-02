create function dp_rights_link_member(in_right_id integer, in_member_id bigint, in_is_active integer) returns integer
  language plpgsql
as
$$
BEGIN
  IF NOT EXISTS(SELECT 1 FROM rights WHERE right_id = in_right_id) THEN
    return 1;
  END IF;

  IF NOT EXISTS(SELECT 1 FROM members WHERE member_id = in_member_id) THEN
    return 2;
  END IF;

  IF (in_is_active > 0) THEN
    INSERT INTO members_rights (member_id, right_id) VALUES(in_member_id, in_right_id);
  ELSE
    DELETE FROM members_rights WHERE member_id = in_member_id AND right_id = in_right_id;
  END IF;

  return 0;
END
$$;