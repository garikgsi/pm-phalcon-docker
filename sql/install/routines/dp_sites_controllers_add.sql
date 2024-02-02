create function dp_sites_controllers_add(in_name varchar(128)) returns integer
  language plpgsql
as
$$
BEGIN
  IF (in_name = '') THEN
    return 0;
  end if;

  IF EXISTS(SELECT 1 FROM sites_controllers WHERE controller_name = in_name) THEN
    return 0;
  end if;

  INSERT INTO sites_controllers (controller_name) VALUES (in_name);

  return currval('sites_controllers_controller_id_seq');
END
$$;