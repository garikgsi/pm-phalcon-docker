create function dp_rights_add_to_app(in_app_id integer, in_name varchar(64), in_parent_id integer) returns integer
  language plpgsql
as $$
declare
  tmp_right_id integer;
begin
  IF NOT EXISTS(SELECT 1 FROM apps WHERE app_id = in_app_id) THEN
    return -2;
  end if;

  SELECT dp_rights_add(in_name, in_parent_id) INTO tmp_right_id;

  if (tmp_right_id <= 0) THEN
    return tmp_right_id;
  end if;

  INSERT INTO rights_to_apps (app_id, right_id) VALUES (in_app_id, tmp_right_id);

  UPDATE rights AS rr SET right_order = (
    SELECT
        COALESCE(MAX(r.right_order), 0) + 1
    FROM
      rights AS r,
      rights_to_apps AS ra
    WHERE
        r.right_id = ra.right_id AND
        ra.app_id = in_app_id AND
        ra.right_id <> tmp_right_id
  )
  WHERE
      rr.right_id = tmp_right_id;

  return tmp_right_id;
end;
$$;