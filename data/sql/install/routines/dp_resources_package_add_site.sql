create function dp_resources_package_add_site(in_package_id integer, in_site_id integer) returns integer
language plpgsql
AS $$
BEGIN
  IF NOT EXISTS(SELECT * FROM sites WHERE site_id = in_site_id) THEN
    RETURN 1;
  END IF;

  IF NOT EXISTS(SELECT * FROM resources_packages WHERE package_id = in_package_id) THEN
    RETURN 2;
  END IF;

  if EXISTS(
      SELECT
        *
      FROM
        resources_packages_to_sites AS rpts
      WHERE
        rpts.site_id = in_site_id AND
        rpts.package_id = in_package_id
  ) THEN
    RETURN 3;
  END IF;

  INSERT INTO resources_packages_to_sites (
    site_id,
    package_id
  )
  VALUES (
    in_site_id,
    in_package_id
  );

  RETURN 0;
END
$$;