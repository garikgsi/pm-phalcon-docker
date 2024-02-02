create or replace function dp_resources_get_list_css_groups(in_site_id integer) returns TABLE(
  package_id   smallint,
  package_name varchar32,
  package_end  smallint,
  package_type varchar32,
  connect_id   smallint
)
language plpgsql
as $$
BEGIN
  RETURN QUERY(
    SELECT
      res.package_id,
      res.package_name,
      res.package_end,
      res.package_type,
      res.connect_id
    FROM
      (
        (
          SELECT
            rp.package_id,
            rp.package_name,
            rp.package_end,
            CAST('package' AS VARCHAR32)                   AS package_type,
            CAST(COALESCE(rpts.package_id, 0) AS SMALLINT) AS connect_id,
            1                                              AS sort_order,
            CAST(
                row_number()
                OVER (
                  ORDER BY
                    rp.package_end ASC,
                    rp.package_order ASC
                  )
                AS INTEGER)                                AS sort_pos
          FROM
            resources_packages AS rp
            LEFT JOIN resources_packages_to_sites AS rpts
              ON
                rpts.package_id = rp.package_id AND
                rpts.site_id = in_site_id :: SMALLINT
          WHERE
            rp.type_id = 1
          ORDER BY
            rp.package_end ASC,
            rp.package_order ASC
        )
        UNION
        (
          SELECT
            rp.package_id,
            rp.package_name,
            rp.package_end,
            CAST('app' AS VARCHAR32)                       AS package_type,
            CAST(COALESCE(rpta.package_id, 0) AS SMALLINT) AS connect_id,
            2                                              AS sort_order,
            CAST(
                row_number()
                OVER (
                  ORDER BY
                    rp.package_end ASC,
                    rp.package_order ASC
                  )
                AS INTEGER)                                AS sort_pos
          FROM
            resources_packages AS rp,
            resources_packages_to_apps AS rpta,
            apps_access_sites AS aas
          WHERE
            rp.type_id = 1 AND
            aas.site_id = in_site_id :: SMALLINT AND
            rpta.app_id = aas.app_id AND
            rp.package_id = rpta.package_id
          ORDER BY
            rp.package_end ASC,
            rp.package_order ASC
        )
      ) AS res
    ORDER BY sort_order ASC, sort_pos ASC
  );
END
$$
;