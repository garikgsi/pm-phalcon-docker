create or replace function dp_groups_edit(in_group_id integer, in_name varchar(64), in_parent_id integer, in_lang_id integer, in_title varchar(64)) returns integer
  language plpgsql
as $$
declare
  tmp_result integer;
begin
  tmp_result = 0;

  IF NOT EXISTS(SELECT 1 FROM groups WHERE group_id = in_group_id) THEN
    return 1; -- Группа не существует
  END IF;

  -----------------------------------------------------------------------------
  -- СМЕНА НЕЙМА ГРУППЫ
  -----------------------------------------------------------------------------

  IF (TRIM(in_name) = '') THEN
    return 2;
  end if;

  -- Апдейтим нейм если можем
  IF (in_name <> '' AND NOT EXISTS(SELECT 1 FROM groups WHERE group_name = in_name AND group_id <> in_group_id)) THEN
    UPDATE groups SET group_name = in_name WHERE group_id = in_group_id;
  ELSE
    return 3; -- название занято
  END IF;

  -----------------------------------------------------------------------------
  -- СМЕНА ЛАНГ ИНФОРМАЦИИ
  -----------------------------------------------------------------------------

  INSERT INTO groups_langs (
    group_id,
    lang_id,
    group_title
  )
  VALUES (
    in_group_id,
    in_lang_id,
    in_title
  )
  ON CONFLICT (group_id, lang_id)
  DO UPDATE SET
    group_title = EXCLUDED.group_title;


  -----------------------------------------------------------------------------
  -- СМЕНА РОДСТВЕННЫХ СВЯЗЕЙ
  -----------------------------------------------------------------------------
  SELECT dp_groups_change_parent(in_group_id, in_parent_id) INTO tmp_result;

  return tmp_result; -- 0 если просто успех, -1 если была смена родителя
end
$$;