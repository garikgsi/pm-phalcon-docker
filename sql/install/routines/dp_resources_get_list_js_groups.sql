create function dp_resources_get_list_js_groups(in_site_id integer)
  returns TABLE(package_id smallint, package_name varchar32, package_end smallint, connect_id smallint)
  language plpgsql
as $$
BEGIN
  RETURN QUERY(
    SELECT
      rp.package_id,
      rp.package_name,
      rp.package_end,
      CAST(COALESCE(rpts.package_id, 0) AS SMALLINT) AS connect_id
    FROM
      resources_packages AS rp
        LEFT JOIN resources_packages_to_sites AS rpts
                  ON
                        rpts.package_id = rp.package_id AND
                        rpts.site_id = in_site_id::SMALLINT
    WHERE
        rp.type_id = 2 AND
        rp.package_compress_group = 0
    ORDER BY
      rp.package_end ASC,
      rp.package_order ASC
  );
END
$$;