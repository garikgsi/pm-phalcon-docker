INSERT INTO resources_packages (package_id, package_system, package_order, type_id, package_name, package_end) VALUES (2, 1, 2, 1, 'eliza_forms', 0);/*--;--*/
INSERT INTO resources_packages (package_id, package_system, package_order, type_id, package_name, package_end) VALUES (1, 1, 1, 1, 'bbcode', 0);/*--;--*/
INSERT INTO resources_packages (package_id, package_system, package_order, type_id, package_name, package_end) VALUES (3, 1, 1, 2, 'vendor', 0);/*--;--*/
INSERT INTO resources_packages (package_id, package_system, package_order, type_id, package_name, package_end) VALUES (4, 1, 2, 2, 'config', 0);/*--;--*/
INSERT INTO resources_packages (package_id, package_system, package_order, type_id, package_name, package_end) VALUES (8, 1, 1, 2, 'ender', 1);/*--;--*/
INSERT INTO resources_packages (package_id, package_system, package_order, type_id, package_name, package_end) VALUES (6, 1, 5, 2, 'handler', 0);/*--;--*/
INSERT INTO resources_packages (package_id, package_system, package_order, type_id, package_name, package_end) VALUES (5, 1, 4, 2, 'lib', 0);/*--;--*/
INSERT INTO resources_packages (package_id, package_system, package_order, type_id, package_name, package_end) VALUES (7, 1, 6, 2, 'builder', 0);/*--;--*/
INSERT INTO resources_packages (package_id, package_system, package_order, type_id, package_name, package_end) VALUES (9, 1, 3, 2, 'jquery', 0);/*--;--*/


SELECT pg_catalog.setval('resources_packages_package_id_seq', 9, true);


