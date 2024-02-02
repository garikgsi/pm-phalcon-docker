CREATE OR REPLACE FUNCTION dp_resources_get_list_js_compiled(in_site_id integer)
  RETURNS TABLE(resource_dir varchar256, resource_name varchar256, resource_minified integer)
LANGUAGE plpgsql
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
              dp_resources_get_list_js(CAST(in_site_id AS SMALLINT)) AS re
        WHERE
          re.connect_id <> 0 AND
          re.exclude_id = 0 AND
          re.resource_type <> 'controller' -- Контроллеры подключаются через RequireJS и ужиматься будут отдельно
      ) AS res
    ORDER BY res.row_number ASC
  );
END
$$;

