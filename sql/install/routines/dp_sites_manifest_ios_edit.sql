create or replace function dp_sites_manifest_ios_edit(in_site_id integer, in_addr_color varchar32, in_touch_color varchar32) returns smallint
language plpgsql
as $$
BEGIN
  -- Проверка существования сайта
  IF NOT EXISTS (SELECT 1 FROM sites AS s WHERE s.site_id = in_site_id) THEN
    RETURN 1; -- Такого сайта не существует
  END IF;

  INSERT INTO sites_manifests (
    site_id,
    manifest_ios_color_addr,
    manifest_ios_color_touch
  )
  VALUES (
    in_site_id,
    in_addr_color,
    in_touch_color
  )
  ON CONFLICT (site_id)
    DO UPDATE SET
      manifest_ios_color_addr  = EXCLUDED.manifest_ios_color_addr,
      manifest_ios_color_touch = EXCLUDED.manifest_ios_color_touch;

  RETURN 0;
END;
$$
;

