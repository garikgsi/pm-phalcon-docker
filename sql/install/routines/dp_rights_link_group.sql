create function dp_rights_link_group(in_right_id integer, in_group_id integer, in_is_active integer) returns integer
  language plpgsql
as
$$
BEGIN
  IF NOT EXISTS(SELECT 1 FROM rights WHERE right_id = in_right_id) THEN
    return 1;
  END IF;

  IF NOT EXISTS(SELECT 1 FROM groups WHERE group_id = in_group_id) THEN
    return 2;
  END IF;

  IF (in_is_active > 0) THEN
    INSERT INTO groups_rights (group_id, right_id) VALUES(in_group_id, in_right_id);
  ELSE
    DELETE FROM groups_rights WHERE group_id = in_group_id AND right_id = in_right_id;
  END IF;

  return 0;
END
$$;