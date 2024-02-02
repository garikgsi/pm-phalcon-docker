create function dp_resources_get_list_css_compiled(in_site_id integer) RETURNS TABLE(resource_dir varchar256, resource_name varchar256, resource_minified integer)

language plpgsql
AS $$
BEGIN
  RETURN QUERY (
    SELECT
      res.resource_dir,
      res.resource_name,
      res.resource_minified
    FROM
      (

        SELECT
          DISTINCT ON (CONCAT(re.resource_dir, re.resource_name))
          re.resource_dir,
          re.resource_name,
          re.resource_minified,
          re.row_number
        FROM
              dp_resources_get_list_css(CAST(in_site_id AS SMALLINT)) AS re
        WHERE
          connect_id <> 0 AND
          exclude_id = 0
      ) AS res
    ORDER BY res.row_number ASC
  );
END
$$;