create function dp_resources_get_list_css(in_site_id integer)
  returns TABLE(resource_id integer, resource_type varchar256, resource_type_id integer, resource_dir varchar256, resource_name varchar256, resource_minified integer, connect_id integer, exclude_id integer, sort_order integer, sort_pos integer, row_number integer)
language plpgsql
as $$
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
                          CAST((CASE WHEN package_end = 0 THEN 1 ELSE 3 END)   AS integer)                        AS sort_order,
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
                       rp.type_id = 1 AND
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
                          r.resource_id,
                          CAST('site' AS varchar256)            AS resource_type,
                          CAST(r.package_id AS integer)         AS resource_type_id,
                          CAST(r.resource_dir  AS varchar256)   AS resource_dir,
                          CAST(r.resource_name AS varchar256)   AS resource_name,
                          CAST(r.resource_minified AS integer)  AS resource_minified,
                          CAST(COALESCE(rts.resource_id, 0) AS integer) AS connect_id,
                          CAST(COALESCE(re.resource_id,  0) AS integer)  AS exclude_id,
                          CAST(2   AS integer)                        AS sort_order,
                          CAST(row_number()
                                   OVER (
                                     ORDER BY
                                       r.resource_order ASC
                                     )   AS integer)                          AS sort_pos
                   FROM
                        resources AS r,
                        resources_to_sites AS rts
                          LEFT JOIN resources_excludes AS re
                            ON
                                re.resource_id = rts.resource_id AND
                                re.site_id = rts.site_id

                   WHERE
                       r.type_id = 1 AND
                       r.package_id IS NULL AND
                       r.resource_id = rts.resource_id AND
                       rts.site_id = in_site_id
                   ORDER BY
                            r.resource_order ASC

                   )
                   UNION
                   (
                   SELECT
                          r.resource_id,
                          CAST('package_app' AS varchar256)         AS resource_type,
                          CAST(r.package_id AS integer)         AS resource_type_id,
                          CAST(r.resource_dir  AS varchar256)   AS resource_dir,
                          CAST(r.resource_name AS varchar256)   AS resource_name,
                          CAST(r.resource_minified AS integer)  AS resource_minified,
                          CAST(COALESCE(rpta.package_id, 0) AS integer) AS connect_id,
                          CAST(COALESCE(re.resource_id, 0) AS integer)  AS exclude_id,
                          CAST((CASE WHEN package_end = 0 THEN 4 ELSE 6 END)   AS integer)                        AS sort_order,
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
                        resources_packages AS rp,
                        resources_packages_to_apps AS rpta,
                        apps_access_sites AS aas
                   WHERE
                       rp.type_id = 1 AND
                       r.package_id = rp.package_id AND
                       rpta.package_id = rp.package_id AND
                       rpta.app_id = aas.app_id AND
                       aas.site_id = in_site_id AND
                       r.type_id = rp.type_id
                   ORDER BY
                            rp.package_end ASC,
                            rp.package_order ASC,
                            r.resource_order ASC

                   )
                   UNION
                   (
                   SELECT
                          r.resource_id,
                          CAST('app' AS varchar256)            AS resource_type,
                          CAST(rta.app_id AS integer)         AS resource_type_id,
                          CAST(r.resource_dir  AS varchar256)   AS resource_dir,
                          CAST(r.resource_name AS varchar256)   AS resource_name,
                          CAST(r.resource_minified AS integer)  AS resource_minified,
                          CAST(COALESCE(rta.resource_id, 0) AS integer) AS connect_id,
                          CAST(COALESCE(re.resource_id,  0) AS integer)  AS exclude_id,
                          CAST(5   AS integer)                        AS sort_order,
                          CAST(row_number()
                                   OVER (
                                     ORDER BY
                                       r.resource_order ASC
                                     )   AS integer)                          AS sort_pos
                   FROM
                        resources AS r,
                        apps_access_sites AS aas,
                        resources_to_apps AS rta
                          LEFT JOIN resources_excludes AS re
                            ON
                                re.resource_id = rta.resource_id AND
                                re.site_id = in_site_id

                   WHERE
                       aas.site_id = in_site_id AND
                       rta.app_id = aas.app_id AND
                       r.type_id = 1 AND
                       r.resource_id = rta.resource_id AND
                       r.package_id IS NULL
                   ORDER BY
                            r.resource_order ASC
                   )

                   ORDER BY sort_order ASC, sort_pos ASC
                   ) AS res
              );
END
$$;

