create function dp_members_change_primary_group(in_member_id bigint, in_group_id integer)
  returns integer
  language plpgsql
as $$
DECLARE
  current_group_id integer;
  current_group_name varchar(64);
  current_group_assignable integer;
  current_group_primary_only integer;
  set_group_id integer;
  set_group_name varchar(64);
  set_group_assignable integer;
  set_group_primary_only integer;
BEGIN
  SELECT
      g.group_id,
      g.group_name,
      g.group_assignable,
      g.group_primary_only
  FROM
      members AS m,
      groups AS g
  WHERE
      g.group_id = m.member_group AND
      m.member_id = in_member_id
  INTO
      current_group_id,
      current_group_name,
      current_group_assignable,
      current_group_primary_only;

  IF (current_group_name IN ('developers', 'guests')) THEN
    RETURN -1; -- эту группу нельзя ПОКИНУТЬ, ОНА ВСЕГДА ПРАЙМАРИ
  END IF;

  SELECT
      g.group_id,
      g.group_name,
      g.group_assignable,
      g.group_primary_only
  FROM
      groups AS g
  WHERE
      g.group_id = in_group_id
  INTO
      set_group_id,
      set_group_name,
      set_group_assignable,
      set_group_primary_only;

  IF (set_group_id IS NULL) THEN
    RETURN -2;
  END IF;

  IF (set_group_assignable = 0) THEN
    RETURN -3; -- эту группу установить праймари, тока через БД
  END IF;

  UPDATE members SET member_group = set_group_id WHERE member_id = in_member_id;

  IF (set_group_name = 'banned') THEN
    DELETE FROM members_groups WHERE member_id = in_member_id;

    RETURN 0;
  ELSE
    DELETE FROM members_groups WHERE member_id = in_member_id AND group_id = set_group_id;
  END IF;

  IF (current_group_primary_only = 1) THEN
    RETURN 0; -- Это значит, что предыдущая группа не подразумевает нахождение в качестве вторичной
  END IF;

  INSERT INTO members_groups (member_id, group_id) VALUES (in_member_id, current_group_id);

  RETURN current_group_id;
END;
$$;