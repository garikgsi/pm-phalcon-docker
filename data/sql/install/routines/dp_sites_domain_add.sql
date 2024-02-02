create or replace function dp_sites_domain_add(in_site_id integer, in_domain_name varchar100, in_lang_id smallint, in_cross integer) returns smallint
language plpgsql
as $$
BEGIN
  in_domain_name = LOWER(in_domain_name);

  -- Проверка уникальности домена
  IF EXISTS (SELECT 1 FROM sites_domains AS sd WHERE sd.domain_name = in_domain_name) THEN
    RETURN 1; -- Такой домен уже существует
  END IF;

  -- Проверка наличия языка
  IF NOT EXISTS (SELECT 1 FROM languages AS l WHERE l.lang_id = in_lang_id) THEN
    RETURN 2; -- Такого языка не существует
  END IF;

  -- Проверка существования сайта
  IF NOT EXISTS (SELECT 1 FROM sites AS s WHERE s.site_id = in_site_id) THEN
    RETURN 3; -- Такого сайта не существует
  END IF;

  INSERT INTO sites_domains (
    site_id,
    domain_name,
    lang_id,
    domain_cross
  )
  VALUES (
    in_site_id,
    in_domain_name,
    in_lang_id,
    in_cross
  );

  RETURN 0;
END;
$$
;

