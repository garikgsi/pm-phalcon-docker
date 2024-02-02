create or replace function dp_groups_change_parent(in_group_id integer, in_parent_id integer) returns integer
  language plpgsql
as $$
declare
  tmp_parent_id integer;
  tmp_result integer;
begin
  tmp_result = 0;

  IF NOT EXISTS(SELECT 1 FROM groups WHERE group_id = in_group_id) THEN
    return 1; -- Группа не существует
  END IF;

  SELECT COALESCE(group_parent_id, 0) FROM groups WHERE group_id = in_group_id INTO tmp_parent_id;

  in_parent_id = COALESCE(in_parent_id, 0);

  IF (in_parent_id <> tmp_parent_id) THEN

    -- Удаляем линки наследования для всех чайлдов редактируемой группы
    DELETE FROM groups_relationships AS grc
    WHERE
        grc.group_id  IN (
        SELECT grg.group_id
        FROM groups_relationships AS grg
        WHERE grg.parent_id = in_group_id
      ) AND
        grc.parent_id IN (
        SELECT grp.parent_id
        FROM groups_relationships AS grp
        WHERE grp.parent_id <> in_group_id AND grp.group_id = in_group_id
      );

    -- Удаляем линки наследования для редактируемой группы
    DELETE FROM groups_relationships AS gr
    WHERE
        gr.group_id = in_group_id AND
        gr.parent_id <> in_group_id;

    -- Если новый родитель не ноль, то надо добавлять новые ссылки наследования
    IF (in_parent_id > 0) THEN

      -- Добавляем ссылки для группы
      INSERT INTO groups_relationships (
        group_id,
        parent_id
      )
      SELECT
        in_group_id AS group_id,
        parent_id
      FROM groups_relationships AS gr
      WHERE
          gr.group_id = in_parent_id;

      -- Добавляем ссылки для потомков группы
      INSERT INTO groups_relationships (
        group_id,
        parent_id
      )
      SELECT
        grr.group_id,
        gr.parent_id
      FROM
        groups_relationships AS gr,
        (
          SELECT grg.group_id
          FROM groups_relationships AS grg
          WHERE
              grg.parent_id = in_group_id AND
              grg.group_id <> in_group_id
        ) AS grr
      WHERE
          gr.group_id = in_parent_id;
    ELSE
      in_parent_id = NULL;
    END IF;



    UPDATE groups SET group_parent_id = in_parent_id WHERE group_id = in_group_id;

    tmp_result = -1;
  END IF;

  return tmp_result; -- 0 если просто успех, -1 если была смена родителя
end
$$;