create function dp_apps_add(in_name character varying) returns integer
  language plpgsql
as
$$
BEGIN
  IF (in_name = '') THEN
    return 0;
  end if;

  IF EXISTS(SELECT 1 FROM apps WHERE app_name = in_name) THEN
    return 0;
  end if;

  INSERT INTO apps (app_name) VALUES (in_name);

  return currval('apps_app_id_seq');
END
$$;