INSERT INTO sites_manifests_orientation (orientation_id, orientation_name) VALUES (1, 'any');/*--;--*/
INSERT INTO sites_manifests_orientation (orientation_id, orientation_name) VALUES (2, 'natural');/*--;--*/
INSERT INTO sites_manifests_orientation (orientation_id, orientation_name) VALUES (3, 'landscape');/*--;--*/
INSERT INTO sites_manifests_orientation (orientation_id, orientation_name) VALUES (4, 'landscape-primary');/*--;--*/
INSERT INTO sites_manifests_orientation (orientation_id, orientation_name) VALUES (5, 'landscape-secondary');/*--;--*/
INSERT INTO sites_manifests_orientation (orientation_id, orientation_name) VALUES (6, 'portrait');/*--;--*/
INSERT INTO sites_manifests_orientation (orientation_id, orientation_name) VALUES (7, 'portrait-primary');/*--;--*/
INSERT INTO sites_manifests_orientation (orientation_id, orientation_name) VALUES (8, 'portrait-secondary');/*--;--*/

SELECT pg_catalog.setval('sites_manifests_orientation_orientation_id_seq', 8, true);
