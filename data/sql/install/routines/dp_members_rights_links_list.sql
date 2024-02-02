CREATE OR REPLACE FUNCTION dp_members_rights_links_list(in_member_id bigint, in_lang_id integer)
  RETURNS TABLE(
    object_id integer,
    object_name varchar(256),
    object_title varchar(256),
    object_type varchar(128),
    parent_id integer,
    right_id integer,
    right_in_use integer,
    right_name varchar(64),
    right_title varchar(256),
    right_description text,
    given_by_group integer,
    linked_to_member integer
  )
  LANGUAGE plpgsql
AS $$
BEGIN
  RETURN QUERY
    SELECT
      rlc.object_id,
      rlc.object_name,
      rlc.object_title,
      CAST(rlc.object_type AS varchar(128)) AS object_type,
      rlc.parent_id,
      rlc.right_id,
      rlc.right_in_use,
      rlc.right_name,
      rlc.right_title,
      rlc.right_description,
      COALESCE(mgl.group_id, 0) AS given_by_group,
      COALESCE(mr.right_id,  0) AS linked_to_member
    FROM
      rights_list_complete AS rlc
      LEFT JOIN groups_rights AS gr ON  gr.right_id = rlc.right_id
      LEFT JOIN members_groups_links AS mgl ON mgl.group_id = gr.group_id AND mgl.member_id = in_member_id
      LEFT JOIN members_rights AS mr ON mr.right_id = rlc.right_id AND mgl.member_id = in_member_id
    WHERE
      rlc.lang_id = in_lang_id;
END;
$$;