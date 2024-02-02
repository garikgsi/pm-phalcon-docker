CREATE OR REPLACE FUNCTION dp_resources_get_list_js(in_site_id integer)
  RETURNS TABLE(resource_id integer, resource_type varchar256, resource_type_id integer, resource_dir varchar256, resource_name varchar256, resource_minified integer, connect_id integer, exclude_id integer, sort_order integer, sort_pos integer, row_number integer)
LANGUAGE plpgsql
AS $$
BEGIN
  in_site_id = CAST(in_site_id AS SMALLINT);

  RETURN QUERY(
    SELECT
      res.*,
      CAST(row_number() OVER() AS integer) as row_number
    FROM
      (
        (
          SELECT
            r.resource_id,
            CAST('package' AS varchar256)         AS resource_type,
            CAST(r.package_id AS integer)         AS resource_type_id,
            CAST(r.resource_dir  AS varchar256)   AS resource_dir,
            CAST(r.resource_name AS varchar256)   AS resource_name,
            CAST(r.resource_minified AS integer)  AS resource_minified,
            CAST(COALESCE(rpts.package_id, 0) AS integer) AS connect_id,
            CAST(COALESCE(re.resource_id, 0) AS integer)  AS exclude_id,
            CAST((CASE WHEN package_end = 0 THEN 1 ELSE 4 END)   AS integer)                        AS sort_order,
            CAST(row_number()
                 OVER (
                   ORDER BY
                     rp.package_order ASC,
                     r.resource_order ASC
                   )   AS integer)                          AS sort_pos
          FROM
            resources AS r
            LEFT JOIN resources_excludes AS re
              ON
                re.resource_id = r.resource_id AND
                re.site_id = in_site_id
            ,
            resources_packages AS rp
            LEFT JOIN resources_packages_to_sites AS rpts
              ON
                rpts.package_id = rp.package_id AND
                rpts.site_id = in_site_id
          WHERE
            rp.type_id = 2 AND
            r.package_id = rp.package_id AND
            r.type_id = rp.type_id
          ORDER BY
            rp.package_end ASC,
            rp.package_order ASC,
            r.resource_order ASC
        )
        UNION
        (
          SELECT
            0 AS resource_id,
            CAST('controller' AS varchar256)                      AS resource_type,
            CAST(sc.controller_id AS integer)                     AS resource_type_id,
            CAST('js/site/' || s.site_name || '/' AS varchar256) AS resource_dir,
            CAST(sc.controller_name || '.js' AS varchar256)       AS resource_name,
            CAST(0  AS integer)                                 AS resource_minified,
            CAST(sc.controller_id  AS integer)                  AS connect_id,
            CAST(0  AS integer)                                 AS exclude_id,
            CAST(2 AS integer)                                  AS sort_order,
            CAST(row_number()
                 OVER (
                   ORDER BY
                     sc.controller_id ASC
                   ) AS integer)                              AS sort_pos
          FROM
            sites AS s,
            sites_controllers_to_sites AS scts,
            sites_controllers AS sc
          WHERE
            s.site_id = in_site_id AND
            scts.site_id = s.site_id AND
            sc.controller_id = scts.controller_id
          ORDER BY
            sc.controller_id ASC
        )
        UNION
        (
          SELECT
            0 AS resource_id,
            CAST('site' AS varchar256)               AS resource_type,
            CAST(s.site_id     AS integer)           AS resource_type_id,
            CAST('js/site/' AS varchar256)          AS resource_dir,
            CAST(s.site_name || '.js' AS varchar256) AS resource_name,
            CAST(0      AS integer)                AS resource_minified,
            CAST(s.site_id  AS integer)            AS connect_id,
            CAST(0   AS integer)                   AS exclude_id,
            CAST(3   AS integer)                   AS sort_order,
            CAST(row_number()
                 OVER (
                   ORDER BY
                     s.site_id ASC
                   )   AS integer)                 AS sort_pos
          FROM
            sites AS s
          WHERE
            s.site_id = in_site_id
          ORDER BY
            s.site_id ASC
        )
        ORDER BY sort_order ASC, sort_pos ASC
      ) AS res
  );
END
$$;

