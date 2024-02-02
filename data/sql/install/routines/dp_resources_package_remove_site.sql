create function dp_resources_package_remove_site(in_package_id integer, in_site_id integer) returns integer
language plpgsql
AS $$
BEGIN
  if NOT EXISTS(
      SELECT
        *
      FROM
        resources_packages_to_sites AS rpts
      WHERE
        rpts.site_id = in_site_id AND
        rpts.package_id = in_package_id
  ) THEN
    RETURN 1;
  END IF;

  DELETE FROM resources_packages_to_sites AS rpts WHERE rpts.site_id = in_site_id AND rpts.package_id = in_package_id;

  RETURN 0;
END
$$;