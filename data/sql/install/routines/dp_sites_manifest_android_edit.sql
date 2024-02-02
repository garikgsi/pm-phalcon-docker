create or replace function dp_sites_manifest_android_edit(in_site_id integer, in_display_id integer, in_orientation_id integer, in_scope varchar100, in_url varchar100, in_addr_color varchar32, in_theme_color varchar32, in_dir varchar8) returns smallint
language plpgsql
as $$
BEGIN
  -- Проверка существования сайта
  IF NOT EXISTS (SELECT 1 FROM sites AS s WHERE s.site_id = in_site_id) THEN
    RETURN 1; -- Такого сайта не существует
  END IF;

  -- Проверка display
  IF NOT EXISTS (SELECT 1 FROM sites_manifests_display AS smd WHERE smd.display_id = in_display_id) THEN
    RETURN 2; -- Такого display не существует
  END IF;

  -- Проверка orientation
  IF NOT EXISTS (SELECT 1 FROM sites_manifests_orientation AS smo WHERE smo.orientation_id = in_orientation_id) THEN
    RETURN 3; -- Такого orientation не существует
  END IF;

  INSERT INTO sites_manifests (
    site_id,
    display_id,
    orientation_id,
    manifest_url,
    manifest_addr_color,
    manifest_dir,
    manifest_theme_color,
    manifest_scope
  )
  VALUES (
    in_site_id,
    in_display_id,
    in_orientation_id,
    in_url,
    in_addr_color,
    in_dir,
    in_theme_color,
    in_scope
  )
  ON CONFLICT (site_id)
    DO UPDATE SET
      display_id           = EXCLUDED.display_id,
      orientation_id       = EXCLUDED.orientation_id,
      manifest_url         = EXCLUDED.manifest_url,
      manifest_addr_color  = EXCLUDED.manifest_addr_color,
      manifest_dir         = EXCLUDED.manifest_dir,
      manifest_theme_color = EXCLUDED.manifest_theme_color,
      manifest_scope       = EXCLUDED.manifest_scope;

  RETURN 0;
END;
$$
;

