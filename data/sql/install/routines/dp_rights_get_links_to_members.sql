create or replace function dp_rights_get_links_to_members(in_member_id bigint, in_lang_id integer) returns table(
  object_id integer,
  object_name varchar(256),
  object_title varchar(256),
  object_type varchar(64),
  parent_id integer,
  right_id integer,
  right_in_use integer,
  right_name varchar(128),
  right_title varchar(256),
  is_active integer
)
  language plpgsql
as
$$
BEGIN
  return QUERY SELECT
    rlc.object_id,
    rlc.object_name,
    rlc.object_title,
    CAST(rlc.object_type AS VARCHAR(64)),
    rlc.parent_id,
    rlc.right_id,
    rlc.right_in_use,
    rlc.right_name,
    rlc.right_title,
    COALESCE(mr.right_id, 0) AS is_active
  FROM
    rights_list_complete AS rlc
      LEFT JOIN members_rights AS mr ON mr.right_id = rlc.right_id AND mr.member_id = in_member_id
  WHERE
      rlc.lang_id = in_lang_id;
END
$$;