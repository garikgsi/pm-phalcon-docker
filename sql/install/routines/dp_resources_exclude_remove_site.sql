create function dp_resources_exclude_remove_site(in_resource_id integer, in_site_id integer) returns integer
language plpgsql
AS $$
BEGIN
  if NOT EXISTS(
      SELECT
        *
      FROM
        resources_excludes AS re
      WHERE
        re.site_id = in_site_id AND
        re.resource_id = in_resource_id
  ) THEN
    RETURN 1;
  END IF;

  DELETE FROM resources_excludes AS re WHERE re.site_id = in_site_id AND re.resource_id = in_resource_id;

  RETURN 0;
END
$$;