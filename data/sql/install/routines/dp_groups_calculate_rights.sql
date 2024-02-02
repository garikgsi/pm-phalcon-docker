create function dp_groups_calculate_rights(in_group_id integer) returns integer
language plpgsql
As $$
declare
  tmp_count integer;
BEGIN
  select
      count(gr.group_id)
  from
      groups_rights AS gr
  where
      gr.group_id = in_group_id
  INTO tmp_count;

  return tmp_count;
END
$$;