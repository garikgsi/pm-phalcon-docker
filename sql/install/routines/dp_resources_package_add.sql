CREATE OR REPLACE FUNCTION dp_resources_package_add(in_package_name varchar256, in_type_id integer)
  RETURNS integer
LANGUAGE plpgsql
AS $$
BEGIN
  IF EXISTS(SELECT * FROM resources_packages WHERE package_name = in_package_name) THEN
    return -1;
  END IF;

  INSERT INTO resources_packages (
    package_order,
    type_id,
    package_name
  ) VALUES (
    (SELECT COALESCE(MAX(package_order), 0) + 1 FROM resources_packages WHERE type_id = in_type_id),
    in_type_id::SMALLINT,
    in_package_name
  );

  RETURN currval('resources_packages_package_id_seq');
END;
$$;
