create function dp_members_change_right_link_toggle(in_member_id bigint, in_right_id integer)
  returns integer
  language plpgsql
as $$
BEGIN
  IF NOT EXISTS(SELECT 1 FROM members WHERE member_id = in_member_id) THEN
    RETURN 1;
  END IF;

  IF NOT EXISTS(SELECT 1 FROM rights WHERE right_id = in_right_id) THEN
    RETURN 2;
  END IF;

  IF EXISTS(SELECT * FROM members_rights WHERE member_id = in_member_id AND right_id = in_right_id) THEN
    DELETE FROM members_rights WHERE member_id = in_member_id AND right_id = in_right_id;
  ELSE
    INSERT INTO members_rights (member_id, right_id) VALUES (in_member_id, in_right_id);
  END IF;

  RETURN 0;
END;
$$;