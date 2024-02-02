create or replace view sites_manifests_html_icons as
  SELECT si.site_id,
    ((si.icon_width || 'x'::text) || si.icon_height) AS icon_size,
    ((((('/uploads/icos/'::text || si.site_id) || '_'::text) || (si.icon_name)::text) || '.'::text) || (si.icon_type)::text) AS icon_uri,
    ('image/'::text || (si.icon_type)::text) AS icon_type
  FROM sites_icons si
  WHERE (((si.icon_name)::text ~~ 'ico_%'::text) OR ((si.icon_name)::text ~~ 'apple_%'::text) OR ((si.icon_name)::text = 'android_192x192'::text))
  ORDER BY si.icon_width, si.icon_height;

