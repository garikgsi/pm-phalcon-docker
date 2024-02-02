CREATE FUNCTION dp_resources_get_list_js_controllers(in_site_id integer)
  RETURNS TABLE(js_path VARCHAR(512))
LANGUAGE plpgsql
AS $$
BEGIN
  RETURN QUERY (
    SELECT
      CAST('site/' || s.site_name || '/' || sc.controller_name AS VARCHAR(512))
    FROM
      sites AS s,
      sites_controllers AS sc,
      sites_controllers_to_sites AS scts
    WHERE
      s.site_id = in_site_id AND
      scts.site_id = s.site_id AND
      sc.controller_id = scts.controller_id
  );
END
$$;