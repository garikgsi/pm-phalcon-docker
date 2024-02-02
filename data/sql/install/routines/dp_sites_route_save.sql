CREATE FUNCTION dp_sites_route_save(in_route_id integer, in_pattern VARCHAR(512), in_json JSON)
  RETURNS INTEGER
LANGUAGE plpgsql
AS $$
DECLARE
  tmp_site_id INTEGER;
BEGIN
  -- Проверка существования маршрута
  IF NOT EXISTS (SELECT 1 FROM sites_routes AS sr WHERE sr.route_id = in_route_id ) THEN
    RETURN 1; -- Такого маршрута не существует
  END IF;

  SELECT site_id INTO tmp_site_id FROM sites_routes AS sr WHERE sr.route_id = in_route_id;


  -- Проверка существования такого-же шаблона
  IF EXISTS (SELECT 1 FROM sites_routes AS sr WHERE sr.site_id = tmp_site_id AND sr.route_pattern = in_pattern ) THEN
    RETURN 2; -- Такой маршрут уже существует
  END IF;

  UPDATE sites_routes SET route_pattern = in_pattern, route_json = in_json WHERE route_id = in_route_id;

  RETURN 0;
END;
$$;