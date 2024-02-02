create or replace function dp_sites_domain_delete(in_domain_name varchar100) returns smallint
language plpgsql
as $$
BEGIN
  in_domain_name = LOWER(in_domain_name);

  -- Проверка существования домена
  IF NOT EXISTS (SELECT 1 FROM sites_domains AS sd WHERE sd.domain_name = in_domain_name) THEN
    RETURN 1; -- Такой домен не существует
  END IF;

  DELETE FROM sites_domains AS sd WHERE sd.domain_name = in_domain_name;

  RETURN 0;
END;
$$
;

