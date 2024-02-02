create function dp_groups_add(in_name varchar(64), in_parent_id integer, in_lang_id integer, in_title varchar(64)) returns integer
  language plpgsql
as $$
declare
  tmp_group_id integer;
begin
  IF (TRIM(in_name) = '') THEN
    return 0;
  end if;

  IF EXISTS(SELECT 1 FROM groups WHERE group_name = in_name) THEN
    return -1;
  end if;

  INSERT INTO groups (group_name) VALUES (in_name);

  tmp_group_id = currval('groups_group_id_seq');

  INSERT INTO groups_relationships (group_id, parent_id) VALUES(tmp_group_id, tmp_group_id);

  if (in_parent_id IS NOT NULL and in_parent_id > 0) then
    INSERT INTO groups_relationships (
      group_id,
      parent_id
    )
    SELECT
      tmp_group_id AS group_id,
      parent_id
    FROM groups_relationships AS gr
    WHERE
        gr.group_id = in_parent_id;

    UPDATE groups SET group_parent_id = in_parent_id WHERE group_id = tmp_group_id;
  end if;

  if EXISTS( SELECT 1 FROM languages WHERE lang_id = in_lang_id) THEN
    INSERT INTO groups_langs (group_title, group_id, lang_id) VALUES (in_title, tmp_group_id, in_lang_id);
  end if;

  return tmp_group_id;
end
$$;