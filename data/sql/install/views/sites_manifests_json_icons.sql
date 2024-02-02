create or replace view sites_manifests_json_icons as
  SELECT si.site_id,
    ((((('/uploads/icos/'::text || si.site_id) || '_'::text) || (si.icon_name)::text) || '.'::text) || (si.icon_type)::text) AS src,
    ((si.icon_width || 'x'::text) || si.icon_height) AS sizes,
    ('image/'::text || (si.icon_type)::text) AS type
  FROM sites_icons si
  WHERE ((si.icon_name)::text ~~ 'android_%'::text)
  ORDER BY si.icon_width, si.icon_height;

