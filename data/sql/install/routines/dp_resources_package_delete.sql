create function dp_resources_package_delete(in_package_id INTEGER) returns INTEGER
LANGUAGE plpgsql
AS $$
BEGIN
  IF NOT EXISTS(SELECT * FROM resources_packages AS rp WHERE rp.package_id = in_package_id::SMALLINT)
  THEN
    RETURN 1;
  END IF;

  IF EXISTS(SELECT * FROM resources_packages_to_apps AS rpta WHERE rpta.package_id = in_package_id::SMALLINT)
  THEN
    RETURN 2;
  END IF;

  IF EXISTS(SELECT * FROM resources_packages_to_sites AS rpts WHERE rpts.package_id = in_package_id::SMALLINT)
  THEN
    RETURN 3;
  END IF;

  DELETE FROM resources AS r           WHERE r.package_id  = in_package_id::SMALLINT;
  DELETE FROM resources_packages AS rp WHERE rp.package_id = in_package_id::SMALLINT;

  RETURN 0;
END;
$$;