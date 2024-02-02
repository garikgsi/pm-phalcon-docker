CREATE or replace FUNCTION dp_resources_package_add_file(in_package_id integer, in_type_name varchar32, in_dir varchar256, in_name varchar256)
  RETURNS integer
LANGUAGE plpgsql
AS $$
DECLARE
  var_file_type_id SMALLINT;
BEGIN
  in_type_name = LOWER(in_type_name);

  SELECT type_id INTO var_file_type_id FROM resources_types WHERE type_file_extension = in_type_name;

  IF (COALESCE(var_file_type_id, 0) = 0) THEN
    RETURN 1; -- ТАКОЙ ТИП ФАЙЛА НЕ СУЩЕСТВУЕТ
  END IF;

  IF NOT EXISTS(SELECT * FROM resources_packages WHERE package_id = in_package_id) THEN
    RETURN 2;
  END IF;

  if EXISTS(
      SELECT * FROM resources
      WHERE
        type_id = var_file_type_id AND
        package_id = in_package_id AND
        resource_dir = in_dir AND
        resource_name = in_name
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
    (SELECT COALESCE(MAX(resource_order), 0) + 1 FROM resources WHERE package_id = in_package_id),
    in_package_id,
    0,
    in_dir,
    in_name
  );

  RETURN 0;
END
$$;