create function dp_resources_package_change_name(in_package_id INTEGER, in_package_name varchar256) RETURNS INTEGER
language plpgsql
AS $$
BEGIN
  IF NOT EXISTS(SELECT * FROM resources_packages WHERE package_id = in_package_id) THEN
    return 1;
  END IF;

  IF EXISTS(SELECT * FROM resources_packages WHERE package_name = in_package_name AND package_id <> in_package_id) THEN
    return 2;
  END IF;

  UPDATE resources_packages
  SET
    package_name = in_package_name
  WHERE
    package_id = in_package_id;

  return 0;
END;
$$;