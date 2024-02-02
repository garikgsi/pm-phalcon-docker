create or replace function dp_sites_icon_add(in_site_id integer, in_icon_name varchar64, in_icon_type varchar8, in_icon_width integer, in_icon_height integer) returns smallint
language plpgsql
as $$
BEGIN
  in_icon_name = LOWER(in_icon_name);
  in_icon_type = LOWER(in_icon_type);

  -- Проверка существования сайта
  IF NOT EXISTS (SELECT 1 FROM sites AS s WHERE s.site_id = in_site_id) THEN
    RETURN 1; -- Такой сайт не существует
  END IF;

  INSERT INTO sites_icons (
    site_id,
    icon_name,
    icon_type,
    icon_width,
    icon_height
  )
  VALUES (
    in_site_id,
    in_icon_name,
    in_icon_type,
    in_icon_width,
    in_icon_height
  )
  ON CONFLICT (site_id, icon_name)
    DO UPDATE SET
      icon_type   = EXCLUDED.icon_type,
      icon_width  = EXCLUDED.icon_width,
      icon_height = EXCLUDED.icon_height;

  RETURN 0;
END;
$$
;

