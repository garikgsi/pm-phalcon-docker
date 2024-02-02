create or replace function dp_sites_manifest_meta_edit(in_site_id integer, in_lang_id smallint, in_name_long varchar100, in_name_short varchar64, in_description varchar256) returns smallint
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
    manifest_name,
    manifest_name_short,
    manifest_description
  )
  VALUES (
    in_site_id,
    in_lang_id,
    in_name_long,
    in_name_short,
    in_description
  )
  ON CONFLICT (site_id, lang_id)
    DO UPDATE SET
      manifest_name        = EXCLUDED.manifest_name,
      manifest_name_short  = EXCLUDED.manifest_name_short,
      manifest_description = EXCLUDED.manifest_description;

  RETURN 0;
END;
$$
;

