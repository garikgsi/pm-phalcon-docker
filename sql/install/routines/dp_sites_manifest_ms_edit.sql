create or replace function dp_sites_manifest_ms_edit(in_site_id integer, in_tile_color varchar32) returns smallint
language plpgsql
as $$
BEGIN
  -- Проверка существования сайта
  IF NOT EXISTS (SELECT 1 FROM sites AS s WHERE s.site_id = in_site_id) THEN
    RETURN 1; -- Такого сайта не существует
  END IF;

  INSERT INTO sites_manifests (
    site_id,
    manifest_ms_color_tile
  )
  VALUES (
    in_site_id,
    in_tile_color
  )
  ON CONFLICT (site_id)
    DO UPDATE SET
      manifest_ms_color_tile = EXCLUDED.manifest_ms_color_tile;

  RETURN 0;
END;
$$
;

