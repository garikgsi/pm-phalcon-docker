create or replace view sites_manifests_json as
  SELECT sm.site_id,
    l.lang_name AS lang,
    smd.display_name AS display,
    smo.orientation_name AS orientation,
    smm.manifest_name AS name,
    smm.manifest_name_short AS short_name,
    smm.manifest_description AS description,
    sm.manifest_url AS start_url,
    sm.manifest_dir AS dir,
    sm.manifest_scope AS scope,
    sm.manifest_theme_color AS theme_color,
    sm.manifest_theme_color AS background_color
  FROM sites_manifests_display smd,
    sites_manifests_orientation smo,
    ((sites_manifests sm
      LEFT JOIN sites_manifests_meta smm ON ((smm.site_id = sm.site_id)))
      LEFT JOIN languages l ON ((l.lang_id = smm.lang_id)))
  WHERE ((smd.display_id = sm.display_id) AND (smo.orientation_id = sm.orientation_id));

