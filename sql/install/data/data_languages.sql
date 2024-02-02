INSERT INTO languages (lang_id, lang_name, lang_title) VALUES (1, 'ru', 'Русский');
/*--;--*/
INSERT INTO languages (lang_id, lang_name, lang_title) VALUES (2, 'en', 'English');
/*--;--*/

SELECT pg_catalog.setval('languages_lang_id_seq', 2, true);