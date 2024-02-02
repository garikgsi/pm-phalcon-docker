create function dp_resources_exclude_add_site(in_resource_id integer, in_site_id integer) returns integer
language plpgsql
AS $$
BEGIN
  IF NOT EXISTS(SELECT * FROM sites WHERE site_id = in_site_id) THEN
    RETURN 1;
  END IF;

  IF NOT EXISTS(SELECT * FROM resources WHERE resource_id = in_resource_id) THEN
    RETURN 2;
  END IF;

  if EXISTS(
      SELECT
        *
      FROM
        resources_excludes AS re
      WHERE
        re.site_id = in_site_id AND
        re.resource_id = in_resource_id
  ) THEN
    RETURN 3;
  END IF;

  INSERT INTO resources_excludes (
    site_id,
    resource_id
  )
  VALUES (
    in_site_id,
    in_resource_id
  );

  RETURN 0;
END
$$;