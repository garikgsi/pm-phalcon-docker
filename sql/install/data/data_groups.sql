INSERT INTO groups (group_id, group_name, group_assignable, group_primary_only) VALUES (1, 'developers', 0, 1);/*--;--*/
INSERT INTO groups (group_id, group_name, group_assignable, group_primary_only) VALUES (2, 'members', 1, 0);/*--;--*/
INSERT INTO groups (group_id, group_name, group_assignable, group_primary_only) VALUES (3, 'guests', 0, 1);/*--;--*/
INSERT INTO groups (group_id, group_name, group_assignable, group_primary_only) VALUES (4, 'waiting', 1, 1);/*--;--*/
INSERT INTO groups (group_id, group_name, group_assignable, group_primary_only) VALUES (5, 'banned', 1, 1);/*--;--*/
INSERT INTO groups (group_id, group_name, group_assignable, group_primary_only) VALUES (6, 'super_moderator', 1, 0);/*--;--*/
INSERT INTO groups (group_id, group_name, group_assignable, group_primary_only) VALUES (7, 'moderator', 1, 0);/*--;--*/

SELECT pg_catalog.setval('groups_group_id_seq', 7, true);

