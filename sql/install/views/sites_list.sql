create or replace view sites_list as
  SELECT s.site_id,
    s.site_name,
    s.site_min_enabled,
    count(sd.domain_name) AS domains
  FROM sites s,
    sites_domains sd
  WHERE (sd.site_id = s.site_id)
  GROUP BY s.site_id, s.site_name, s.site_min_enabled
  ORDER BY s.site_id;

