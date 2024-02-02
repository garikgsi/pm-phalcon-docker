create function dp_rights_delete(in_right_id integer) returns integer
  language plpgsql
as
$$
declare
  tmp_parent_id integer;
BEGIN
  IF EXISTS(SELECT 1 FROM rights WHERE right_id = in_right_id AND right_in_use = 1) THEN
    return 1;
  END IF;

  SELECT right_parent_id FROM rights WHERE right_id = in_right_id INTO tmp_parent_id;

  -- Обновляем права, которые могут быть дочерними по отношению к текущему. Писваиваем им нового родителя
  UPDATE rights SET right_parent_id = tmp_parent_id WHERE right_parent_id = in_right_id;

  DELETE FROM rights_relationships WHERE parent_id = in_right_id;
  DELETE FROM rights_langs WHERE right_id = in_right_id;

  DELETE FROM rights_to_apps WHERE right_id = in_right_id;
  DELETE FROM rights_to_controllers WHERE right_id = in_right_id;
  DELETE FROM rights_to_sites WHERE right_id = in_right_id;

  DELETE FROM rights WHERE right_id = in_right_id;
  return 0;
END
$$;