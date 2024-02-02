create view apps_list AS
SELECT
  a.app_id,
  a.app_access,
  a.app_all_sites,
  a.app_name,
  al.app_title,
  al.app_slogan,
  al.app_description,
  l.lang_id
FROM
  languages AS l
    CROSS JOIN apps AS a
    LEFT JOIN apps_langs al ON al.app_id = a.app_id AND al.lang_id = l.lang_id
ORDER BY a.app_id ASC;