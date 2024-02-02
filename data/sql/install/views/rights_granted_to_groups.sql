create or replace view rights_granted_to_groups AS
SELECT
    gl.lang_id,
    gl.group_id,
    gl.group_parent_id,
    gl.group_system,
    gl.group_name,
    gl.group_title,
    rlm.right_id,
    rlm.right_name,
    rlm.right_in_use,
    rlm.right_title,
    rlm.right_description,
    0 AS right_inheritance
FROM
    rights_list_complete AS rlm,
    groups_rights AS gr,
    groups_list AS gl
WHERE
    gr.right_id = rlm.right_id AND
    gl.group_id = gr.group_id AND
    gl.lang_id = rlm.lang_id

UNION

SELECT
    gl.lang_id,
    gl.group_id,
    gl.group_parent_id,
    gl.group_system,
    gl.group_name,
    gl.group_title,
    rlm.right_id,
    rlm.right_name,
    rlm.right_in_use,
    rlm.right_title,
    rlm.right_description,
    grl.parent_id AS right_inheritance
FROM
    groups_rights AS gr,
    groups_relationships AS grl,
    rights_list_complete AS rlm,
    groups_list AS gl
WHERE
        gr.right_given_to_children = 1 AND
        grl.parent_id = gr.group_id AND
        grl.group_id <> grl.parent_id AND
        rlm.right_id= gr.right_id AND
        gl.group_id = grl.group_id AND
        gl.lang_id = rlm.lang_id

ORDER BY group_title;