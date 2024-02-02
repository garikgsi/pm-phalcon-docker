CREATE FUNCTION dp_sites_add(in_site_name varchar32, in_domain_name varchar100)
  RETURNS smallint
LANGUAGE plpgsql
AS $$
DECLARE
  new_site_id integer;
BEGIN
  in_domain_name = LOWER(in_domain_name);
  in_site_name   = LOWER(in_site_name);

  -- Проверка уникальности названия сайта
  IF EXISTS (SELECT 1 FROM sites AS s WHERE s.site_name = in_site_name) THEN
    RETURN -3; -- Такой домен уже существует
  END IF;

  -- Проверка уникальности домена
  IF EXISTS (SELECT 1 FROM sites_domains AS sd WHERE sd.domain_name = in_domain_name) THEN
    RETURN -1; -- Такой домен уже существует
  END IF;

  INSERT INTO sites (site_name) VALUES (in_site_name);

  new_site_id = currval('sites_site_id_seq');

  INSERT INTO sites_domains (site_id, domain_name, lang_id) VALUES (new_site_id, in_domain_name, 1);

  INSERT INTO sites_manifests (site_id) VALUES(new_site_id);

  INSERT INTO sites_manifests_meta (site_id, lang_id) VALUES(new_site_id, 1);

  INSERT INTO sites_meta (site_id, lang_id) VALUES(new_site_id, 1);

  RETURN new_site_id;
END;
$$;

