create function dp_apps_edit(in_app_id integer, in_lang_id integer, in_name varchar(32), in_title varchar(128), in_slogan varchar(256), in_description text) returns integer
  language plpgsql
as
$$
BEGIN
  IF NOT EXISTS(SELECT 1 FROM apps WHERE app_id = in_app_id) THEN
    return 1;
  END IF;


  IF (in_name <> '' AND NOT EXISTS(SELECT 1 FROM apps WHERE app_name = in_name)) THEN
    UPDATE apps SET app_name = in_name WHERE app_id = in_app_id;
  END IF;

  INSERT INTO apps_langs (app_id, lang_id, app_title, app_description, app_slogan)
  VALUES (in_app_id, in_lang_id, in_title, in_description, in_slogan)
  ON CONFLICT (app_id, lang_id)
     DO UPDATE SET
       app_title = EXCLUDED.app_title,
       app_slogan = EXCLUDED.app_slogan,
       app_description = EXCLUDED.app_description;
  return 0;
END
$$;