create or replace function dp_sites_manifest_ogtc_meta(in_site_id integer, in_lang_id smallint, in_title varchar100, in_description varchar256) returns smallint
language plpgsql
as $$
BEGIN
  -- Проверка существования сайта
  IF NOT EXISTS (SELECT 1 FROM sites AS s WHERE s.site_id = in_site_id) THEN
    RETURN 1; -- Такого сайта не существует
  END IF;

  -- Проверка наличия языка
  IF NOT EXISTS (SELECT 1 FROM languages AS l WHERE l.lang_id = in_lang_id) THEN
    RETURN 2; -- Такого языка не существует
  END IF;

  INSERT INTO sites_manifests_meta (
    site_id,
    lang_id,
    ogtc_title,
    ogtc_description
  )
  VALUES (
    in_site_id,
    in_lang_id,
    in_title,
    in_description
  )
  ON CONFLICT (site_id, lang_id)
    DO UPDATE SET
      ogtc_title        = EXCLUDED.ogtc_title,
      ogtc_description  = EXCLUDED.ogtc_description;

  RETURN 0;
END;
$$
;

