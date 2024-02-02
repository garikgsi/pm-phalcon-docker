INSERT INTO resources_types (type_id, type_name, type_file_extension, type_file_mime) VALUES (1, 'CSS', 'css', 'text/css');/*--;--*/
INSERT INTO resources_types (type_id, type_name, type_file_extension, type_file_mime) VALUES (2, 'JavaScript', 'js ', 'text/javascript');/*--;--*/

SELECT pg_catalog.setval('resources_types_type_id_seq', 2, true);


