CREATE FUNCTION dp_sites_route_default_switch(in_site_id integer, in_enabled integer)
  RETURNS INTEGER
LANGUAGE plpgsql
AS $$
BEGIN
  -- Проверка существования сайта
  IF NOT EXISTS (SELECT 1 FROM sites AS s WHERE s.site_id = in_site_id ) THEN
    RETURN 1; -- Такого сайта не существует
  END IF;

  UPDATE sites SET site_routing_default = in_enabled WHERE site_id = in_site_id;

  RETURN 0;
END;
$$;