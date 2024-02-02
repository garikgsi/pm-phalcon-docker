create function dp_sites_controllers_edit(in_controller_id integer, in_lang_id integer, in_name varchar(128), in_title varchar(256), in_description text) returns integer
  language plpgsql
as
$$
declare
  tmp_locked integer;
BEGIN
  SELECT controller_locked FROM sites_controllers WHERE controller_id = in_controller_id INTO tmp_locked;

  IF (tmp_locked IS NULL) THEN
    return 1;
  end if;

  IF (tmp_locked = 0 AND NOT in_name = '' AND NOT EXISTS(SELECT 1 FROM sites_controllers WHERE controller_name = in_name)) THEN
    UPDATE sites_controllers SET controller_name = in_name WHERE controller_id = in_controller_id;
  end if;

  INSERT INTO sites_controllers_langs (
    controller_id,
    lang_id,
    controller_title,
    controller_description
  )
  VALUES (
           in_controller_id,
           in_lang_id,
           in_title,
           in_description
         )
  ON CONFLICT (controller_id, lang_id)
     DO UPDATE SET
       controller_title = EXCLUDED.controller_title,
       controller_description = EXCLUDED.controller_description;

  return 0;
END
$$;