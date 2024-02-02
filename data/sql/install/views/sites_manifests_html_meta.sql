create or replace view sites_manifests_html_meta as
  SELECT sm.site_id,
    l.lang_name AS lang,
    smm.manifest_name_short AS name,
    sm.manifest_addr_color AS addr_chrome,
    sm.manifest_ios_color_addr AS addr_safari,
    sm.manifest_ios_color_touch AS touch_color,
    sm.manifest_ms_color_tile AS tile_color
  FROM ((sites_manifests sm
    LEFT JOIN sites_manifests_meta smm ON ((smm.site_id = sm.site_id)))
    LEFT JOIN languages l ON ((l.lang_id = smm.lang_id)));

