create or replace function dp_sites_controllers_activated(in_site_id integer, in_lang_id integer) returns TABLE(
  controller_id integer,
  lang_id SMALLINT,
  controller_name  varchar128,
  controller_title varchar256,
  controller_description text,
  active integer
)
language plpgsql
as $$
BEGIN

  return QUERY
  SELECT
    scl.controller_id,
    scl.lang_id,
    scl.controller_name,
    scl.controller_title,
    scl.controller_description,
    COALESCE(scts.site_id, 0) AS active
  FROM
    sites_controllers_list AS scl
    LEFT JOIN sites_controllers_to_sites AS scts
      ON
        scts.controller_id = scl.controller_id AND
        scts.site_id = in_site_id
  WHERE
    scl.lang_id = in_lang_id::SMALLINT
  ORDER BY
    active DESC,
    scl.controller_title ASC;
END
$$
;