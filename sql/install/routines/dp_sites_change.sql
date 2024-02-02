create or replace function dp_sites_change(in_site_id integer, in_site_name varchar32) returns smallint
language plpgsql
as $$
BEGIN
  in_site_name   = LOWER(in_site_name);

  -- Проверка уникальности названия сайта
  IF EXISTS (SELECT 1 FROM sites AS s WHERE s.site_name = in_site_name) THEN
    RETURN 1; -- Такой сайт уже существует
  END IF;

  -- Проверка блокировки сайта на изменение папки
  IF EXISTS (SELECT 1 FROM sites AS s WHERE s.site_id = in_site_id AND s.site_lock = 1) THEN
    RETURN 3; -- Сайт заблокирован на изменение папки
  END IF;

  UPDATE sites SET site_name = in_site_name WHERE site_id = in_site_id;

  RETURN 0;
END;
$$
;

