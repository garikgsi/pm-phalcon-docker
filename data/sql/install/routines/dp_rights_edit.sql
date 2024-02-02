create function dp_rights_edit(in_right_id integer, in_lang_id integer, in_name varchar(64), in_title varchar(256), in_description text) returns integer
  language plpgsql
as
$$
BEGIN
  IF NOT EXISTS(SELECT 1 FROM rights WHERE right_id = in_right_id) THEN
    return 1;
  END IF;

  IF (in_name <> '' AND NOT EXISTS(SELECT 1 FROM rights WHERE right_name = in_name)) THEN
    UPDATE rights SET right_name = in_name WHERE right_id = in_right_id;
  END IF;

  INSERT INTO rights_langs (right_id, lang_id, right_title, right_description)
  VALUES (in_right_id, in_lang_id, in_title, in_description)
  ON CONFLICT (right_id, lang_id)
  DO UPDATE SET
    right_title = EXCLUDED.right_title,
    right_description = EXCLUDED.right_description;

  return 0;
END
$$;