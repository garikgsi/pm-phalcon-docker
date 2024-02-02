create function dp_rights_add_to_site(in_site_id integer, in_name varchar(64), in_parent_id integer) returns integer
  language plpgsql
as $$
declare
  tmp_right_id integer;
begin
  IF NOT EXISTS(SELECT 1 FROM sites WHERE site_id = in_site_id) THEN
    return -2;
  end if;

  SELECT dp_rights_add(in_name, in_parent_id) INTO tmp_right_id;

  if (tmp_right_id <= 0) THEN
    return tmp_right_id;
  end if;

  INSERT INTO rights_to_sites (site_id, right_id) VALUES (in_site_id, tmp_right_id);

  UPDATE rights AS rr SET right_order = (
    SELECT
        COALESCE(MAX(r.right_order), 0) + 1
    FROM
      rights AS r,
      rights_to_sites AS rs
    WHERE
        r.right_id = rs.right_id AND
        rs.site_id = in_site_id AND
        rs.right_id <> tmp_right_id
  )
  WHERE
      rr.right_id = tmp_right_id;

  return tmp_right_id;
end;
$$;