create or replace view sites_manifests_ogtc_meta as
  SELECT sm.site_id,
    l.lang_name AS lang,
    sm.ogtc_og_site AS og_site,
    sm.ogtc_twitter_account AS twitter_account,
    sm.ogtc_twitter_mode AS twitter_mode,
    smm.ogtc_title AS title,
    smm.ogtc_description AS description
  FROM ((sites_manifests sm
    LEFT JOIN sites_manifests_meta smm ON ((smm.site_id = sm.site_id)))
    LEFT JOIN languages l ON ((l.lang_id = smm.lang_id)));

