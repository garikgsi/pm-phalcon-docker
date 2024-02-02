create or replace function dp_sites_meta_edit(in_site_id integer, in_lang_id smallint, in_meta_title varchar100, in_meta_keywords varchar150, in_meta_description varchar150, in_email_name varchar(64), in_email_signature varchar(64)) returns smallint
language plpgsql
as $$
BEGIN
  -- Проверка наличия языка
  IF NOT EXISTS (SELECT 1 FROM languages AS l WHERE l.lang_id = in_lang_id) THEN
    RETURN 1; -- Такого языка не существует
  END IF;

  -- Проверка существования сайта
  IF NOT EXISTS (SELECT 1 FROM sites AS s WHERE s.site_id = in_site_id) THEN
    RETURN 2; -- Такого сайта не существует
  END IF;

  INSERT INTO sites_meta (
    site_id,
    lang_id,
    meta_title,
    meta_keywords,
    meta_description,
    email_name,
    email_signature
  )
  VALUES (
    in_site_id,
    in_lang_id,
    in_meta_title,
    in_meta_keywords,
    in_meta_description,
    in_email_name,
    in_email_signature
  )
  ON CONFLICT (site_id, lang_id)
    DO UPDATE SET
      meta_title       = EXCLUDED.meta_title,
      meta_keywords    = EXCLUDED.meta_keywords,
      meta_description = EXCLUDED.meta_description,
      email_name = EXCLUDED.email_name,
      email_signature = EXCLUDED.email_signature;

  RETURN 0;
END;
$$
;

