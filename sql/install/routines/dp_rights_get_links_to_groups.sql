create or replace function dp_rights_get_links_to_groups(in_group_id integer, in_lang_id integer) returns table(
  object_id integer,
  object_name varchar(256),
  object_title varchar(256),
  object_type varchar(64),
  parent_id integer,
  right_id integer,
  right_in_use integer,
  right_name varchar(128),
  right_title varchar(256),
  is_active integer,
  is_inherit integer,
  right_given_to_children integer
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
    COALESCE(gr.group_id, 0) AS is_active,
    COALESCE(gri.right_id, 0) AS is_inherit,
    COALESCE(gr.right_given_to_children, 0) AS right_given_to_children
  FROM
    rights_list_complete AS rlc
      LEFT JOIN groups_rights AS gr ON gr.right_id = rlc.right_id AND gr.group_id = in_group_id
      LEFT JOIN (
        SELECT
          grl.group_id,
          grs.right_id
        FROM
          groups_rights AS grs,
          groups_relationships AS grl
        WHERE
          grs.right_given_to_children = 1 AND
          grl.parent_id = grs.group_id AND
          grl.group_id <> grl.parent_id
      ) AS gri ON gri.right_id = rlc.right_id AND gri.group_id = in_group_id
  WHERE
      rlc.lang_id = in_lang_id;
END
$$;