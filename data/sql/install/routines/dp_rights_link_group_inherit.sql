create function dp_rights_link_group_inherit(in_right_id integer, in_group_id integer, in_is_given_to_children integer) returns integer
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

  UPDATE
    groups_rights
  SET
    right_given_to_children = in_is_given_to_children
  WHERE
    group_id = in_group_id AND
    right_id = in_right_id;

  return 0;
END
$$;