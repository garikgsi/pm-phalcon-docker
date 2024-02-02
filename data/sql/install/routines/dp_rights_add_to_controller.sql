create function dp_rights_add_to_controller(in_controller_id integer, in_name varchar(64), in_parent_id integer) returns integer
  language plpgsql
as $$
declare
  tmp_right_id integer;
begin
  IF NOT EXISTS(SELECT 1 FROM sites_controllers WHERE controller_id = in_controller_id) THEN
    return -2;
  end if;

  SELECT dp_rights_add(in_name, in_parent_id) INTO tmp_right_id;

  if (tmp_right_id <= 0) THEN
    return tmp_right_id;
  end if;

  INSERT INTO rights_to_controllers (controller_id, right_id) VALUES (in_controller_id, tmp_right_id);

  UPDATE rights AS rr SET right_order = (
    SELECT
        COALESCE(MAX(r.right_order), 0) + 1
    FROM
      rights AS r,
      rights_to_controllers AS rc
    WHERE
        r.right_id = rc.right_id AND
        rc.controller_id = in_controller_id AND
        rc.right_id <> tmp_right_id
  )
  WHERE
      rr.right_id = tmp_right_id;

  return tmp_right_id;
end;
$$;