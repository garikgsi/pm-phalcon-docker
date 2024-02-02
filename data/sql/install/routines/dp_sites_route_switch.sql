CREATE FUNCTION dp_sites_route_switch(in_route_id integer, in_disabled integer)
  RETURNS INTEGER
LANGUAGE plpgsql
AS $$
BEGIN
  -- Проверка существования маршрута
  IF NOT EXISTS (SELECT 1 FROM sites_routes AS sr WHERE sr.route_id = in_route_id ) THEN
    RETURN 1; -- Такого маршрута не существует
  END IF;


  UPDATE sites_routes SET route_disabled = in_disabled WHERE route_id = in_route_id;

  RETURN 0;
END;
$$;