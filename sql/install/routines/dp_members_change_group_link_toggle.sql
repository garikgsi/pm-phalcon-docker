create function dp_members_change_group_link_toggle(in_member_id bigint, in_group_id integer)
  returns integer
  language plpgsql
as $$
DECLARE
  primary_group_id integer;
  primary_group_name varchar(64);
  primary_group_assignable integer;
  primary_group_primary_only integer;
  link_group_id integer;
  link_group_name varchar(64);
  link_group_assignable integer;
  link_group_primary_only integer;
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
      primary_group_id,
      primary_group_name,
      primary_group_assignable,
      primary_group_primary_only;

  IF (primary_group_name IN ('developers', 'guests')) THEN
    RETURN -1; -- эту группу нельзя ПОКИНУТЬ, ОНА ВСЕГДА ПРАЙМАРИ
  END IF;

  IF (primary_group_name = 'banned') THEN
    RETURN -2; -- человек забанен, сначала надо менять праймари группу
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
      link_group_id,
      link_group_name,
      link_group_assignable,
      link_group_primary_only;

  IF (link_group_id IS NULL) THEN
    RETURN -3;
  END IF;

  IF (link_group_assignable = 0) THEN
    RETURN -4; -- эту группу выдавать нельзя
  END IF;

  -- для ожидающих прописку пускаем по ветке активации
  IF (primary_group_name = 'waiting') THEN
    -- Сначала добавляем как дочернюю группу обычных пользователей
    IF (in_group_id <> 2) THEN
      INSERT INTO members_groups (member_id, group_id) VALUES (in_member_id, 2);
    END IF;

    PERFORM dp_members_change_primary_group(in_member_id, in_group_id);

    RETURN 1; -- это значит, что установленная группа встала как праймари
  END IF;

  IF (primary_group_id = link_group_id) THEN
    RETURN -5; -- эту группу является праймари
  END IF;

  IF EXISTS(SELECT * FROM members_groups WHERE member_id = in_member_id AND group_id = in_group_id) THEN
    DELETE FROM members_groups WHERE member_id = in_member_id AND group_id = in_group_id;
  ELSE
    INSERT INTO members_groups (member_id, group_id) VALUES (in_member_id, in_group_id);
  END IF;

  RETURN 0;
END;
$$;