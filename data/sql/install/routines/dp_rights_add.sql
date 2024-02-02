create function dp_rights_add(in_name varchar(64), in_parent_id integer) returns integer
  language plpgsql
as $$
declare
  tmp_right_id integer;
begin
  IF (TRIM(in_name) = '') THEN
    return 0;
  end if;

  IF EXISTS(SELECT 1 FROM rights WHERE right_name = in_name) THEN
    return -1;
  end if;

  INSERT INTO rights (right_name, right_order)
  VALUES (
           in_name,
           (SELECT (COALESCE(MAX(r.right_order), 0) + 1) FROM rights AS r WHERE r.right_common = 1)
         );

  tmp_right_id = currval('rights_right_id_seq');

  INSERT INTO rights_relationships (right_id, parent_id) VALUES (tmp_right_id, tmp_right_id);

  if (in_parent_id IS NOT NULL and in_parent_id > 0) then
    INSERT INTO rights_relationships (
      right_id,
      parent_id
    )
    SELECT
      tmp_right_id AS right_id,
      parent_id
    FROM rights_relationships AS rr
    WHERE
        rr.right_id = in_parent_id;

    UPDATE rights SET right_parent_id = in_parent_id WHERE right_id = tmp_right_id;
  end if;

  return tmp_right_id;
end;
$$;