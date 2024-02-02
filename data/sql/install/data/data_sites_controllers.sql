INSERT INTO sites_controllers (controller_id, controller_name) VALUES (1, 'index');/*--;--*/
INSERT INTO sites_controllers (controller_id, controller_name) VALUES (2, 'resources');/*--;--*/
INSERT INTO sites_controllers (controller_id, controller_name) VALUES (3, 'sites');/*--;--*/


SELECT pg_catalog.setval('sites_controllers_controller_id_seq', 3, true);

