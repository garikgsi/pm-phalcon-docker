CREATE FUNCTION dp_sites_route_add(in_site_id integer)
  RETURNS INTEGER
LANGUAGE plpgsql
AS $$
DECLARE
  new_route_id integer;
BEGIN
  -- Проверка уникальности вновь создаваемого маршрута
  IF EXISTS (SELECT 1 FROM sites_routes AS sr WHERE sr.site_id = in_site_id AND sr.route_pattern = '') THEN
    RETURN -1; -- Такой домен уже существует
  END IF;


  INSERT INTO sites_routes (
    site_id,
    route_pattern,
    route_order
  )
  VALUES (
    in_site_id,
    '',
    (SELECT MAX(sr.route_order) + 1 FROM sites_routes AS sr WHERE sr.site_id = in_site_id)
  );

  new_route_id = currval('sites_routes_route_id_seq');

  RETURN new_route_id;
END;
$$;