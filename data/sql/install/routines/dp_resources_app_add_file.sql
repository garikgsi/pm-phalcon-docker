create or replace function dp_resources_app_add_file(in_app_id integer, in_type_name varchar32, in_dir varchar256, in_name varchar256) returns integer
language plpgsql
as $$
DECLARE
  var_file_type_id SMALLINT;
BEGIN
  in_type_name = LOWER(in_type_name);

  SELECT type_id INTO var_file_type_id FROM resources_types WHERE type_file_extension = in_type_name;

  IF (COALESCE(var_file_type_id, 0) = 0) THEN
    RETURN 1; -- ТАКОЙ ТИП ФАЙЛА НЕ СУЩЕСТВУЕТ
  END IF;

  IF NOT EXISTS(SELECT * FROM apps WHERE app_id = in_app_id) THEN
    RETURN 2;
  END IF;

  if EXISTS(
      SELECT
        *
      FROM
        resources AS r,
        resources_to_apps AS rta
      WHERE
        r.type_id = var_file_type_id AND
        r.package_id IS NULL AND
        r.resource_dir = in_dir AND
        r.resource_name = in_name AND
        rta.app_id = in_app_id AND
        rta.resource_id = r.resource_id
  ) THEN
    RETURN 3;
  END IF;

  INSERT INTO resources (
    type_id,
    resource_order,
    package_id,
    resource_minified,
    resource_dir,
    resource_name
  )
  VALUES (
    var_file_type_id,
    (SELECT
       MAX(r.resource_order) + 1
     FROM
       resources AS r,
       resources_to_apps AS rta
     WHERE
       r.type_id = var_file_type_id AND
       r.package_id IS NULL AND
       r.resource_dir = in_dir AND
       r.resource_name = in_name AND
       rta.app_id = in_app_id AND
       rta.resource_id = r.resource_id),
    NULL,
    0,
    in_dir,
    in_name
  );

  INSERT INTO resources_to_apps (
    resource_id,
    app_id
  )
  VALUES (
    currval('resources_resource_id_seq'),
    in_app_id
  );

  RETURN 0;
END
$$
;

