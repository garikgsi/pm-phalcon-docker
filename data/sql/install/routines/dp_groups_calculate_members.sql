create function dp_groups_calculate_members(in_group_id integer) returns integer
language plpgsql
As $$
declare
  tmp_count integer;
BEGIN
  SELECT
    SUM(res.mcount)
  FROM
    (
      select
        count(member_id) AS mcount
      from
        members AS m
      where
          m.member_group = in_group_id
      UNION
      select
        count(member_id) AS mcount
      from
        members_groups AS mg
      where
          mg.group_id = in_group_id
    ) AS res
  INTO tmp_count;

  return tmp_count;
END
$$;