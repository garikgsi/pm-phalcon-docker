create or replace function dp_rights_change_parent(in_right_id integer, in_parent_id integer) returns integer
  language plpgsql
as $$
declare
  tmp_parent_id integer;
  tmp_result integer;
begin
  tmp_result = 0;

  IF NOT EXISTS(SELECT 1 FROM rights WHERE right_id = in_right_id) THEN
    return 1; -- Группа не существует
  END IF;

  SELECT COALESCE(right_parent_id, 0) FROM rights WHERE right_id = in_right_id INTO tmp_parent_id;

  in_parent_id = COALESCE(in_parent_id, 0);

  IF (in_parent_id <> tmp_parent_id) THEN

    -- Удаляем линки наследования для всех чайлдов редактируемой группы
    DELETE FROM rights_relationships AS rrc
    WHERE
        rrc.right_id  IN (
        SELECT rrg.right_id
        FROM rights_relationships AS rrg
        WHERE rrg.parent_id = in_right_id
      ) AND
        rrc.parent_id IN (
        SELECT rrp.parent_id
        FROM rights_relationships AS rrp
        WHERE rrp.parent_id <> in_right_id AND rrp.right_id = in_right_id
      );

    -- Удаляем линки наследования для редактируемой группы
    DELETE FROM rights_relationships AS rr
    WHERE
        rr.right_id = in_right_id AND
        rr.parent_id <> in_right_id;

    -- Если новый родитель не ноль, то надо добавлять новые ссылки наследования
    IF (in_parent_id > 0) THEN

      -- Добавляем ссылки для группы
      INSERT INTO rights_relationships (
        right_id,
        parent_id
      )
      SELECT
        in_right_id AS right_id,
        parent_id
      FROM rights_relationships AS rr
      WHERE
          rr.right_id = in_parent_id;

      -- Добавляем ссылки для потомков группы
      INSERT INTO rights_relationships (
        right_id,
        parent_id
      )
      SELECT
        rrr.right_id,
        rr.parent_id
      FROM
        rights_relationships AS rr,
        (
          SELECT rrg.right_id
          FROM rights_relationships AS rrg
          WHERE
              rrg.parent_id = in_right_id AND
              rrg.right_id <> in_right_id
        ) AS rrr
      WHERE
          rr.right_id = in_parent_id;
    ELSE
      in_parent_id = NULL;
    END IF;

    UPDATE rights SET right_parent_id = in_parent_id WHERE right_id = in_right_id;

    tmp_result = -1;
  END IF;

  return tmp_result; -- 0 если просто успех, -1 если была смена родителя
end
$$;