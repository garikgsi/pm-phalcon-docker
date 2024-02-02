create or replace view sites_manifests_ms_icons as
  SELECT si.site_id,
    ((((((((('<'::text ||
             CASE
             WHEN ((si.icon_width = 310) AND (si.icon_height = 150)) THEN 'wide'::text
             ELSE 'square'::text
             END) || si.icon_width) || 'x'::text) || si.icon_height) || 'logo src="/uploads/ico/'::text) || (si.icon_name)::text) || '.'::text) || (si.icon_type)::text) || '"/>'::text) AS icon_xml
  FROM sites_icons si
  WHERE ((si.icon_name)::text ~~ 'microsoft_%'::text)
  ORDER BY si.icon_width, si.icon_height;

