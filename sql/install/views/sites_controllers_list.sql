create or replace view sites_controllers_list as
SELECT
  sc.controller_id,
  sc.controller_name,
  sc.controller_locked,
  sc.lang_id,
  sc.sites_count,
  scl.controller_title,
  scl.controller_description
FROM
  (
    SELECT
      *
    FROM
      languages,
      (
        SELECT
          scm.controller_id,
          scm.controller_name,
          scm.controller_locked,
          SUM(CASE WHEN scts.site_id IS NULL THEN 0 ELSE 1 END) AS sites_count
        FROM
          sites_controllers AS scm
            LEFT JOIN sites_controllers_to_sites AS scts ON scts.controller_id = scm.controller_id
        GROUP BY
          scm.controller_id,
          scm.controller_name
      ) AS sct
  ) AS sc
    LEFT JOIN sites_controllers_langs as scl
              ON
                    scl.controller_id = sc.controller_id AND
                    scl.lang_id = sc.lang_id
ORDER BY sc.controller_name ASC;

