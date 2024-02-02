create or replace function dp_sites_manifest_ogtc_edit(in_site_id integer, in_og_site varchar100, in_tc_account varchar256, in_tc_mode integer) returns smallint
language plpgsql
as $$
BEGIN
  -- Проверка существования сайта
  IF NOT EXISTS (SELECT 1 FROM sites AS s WHERE s.site_id = in_site_id) THEN
    RETURN 1; -- Такого сайта не существует
  END IF;

  INSERT INTO sites_manifests (
    site_id,
    ogtc_og_site,
    ogtc_twitter_account,
    ogtc_twitter_mode
  )
  VALUES (
    in_site_id,
    in_og_site,
    in_tc_account,
    in_tc_mode
  )
  ON CONFLICT (site_id)
    DO UPDATE SET
      ogtc_og_site         = EXCLUDED.ogtc_og_site,
      ogtc_twitter_account = EXCLUDED.ogtc_twitter_account,
      ogtc_twitter_mode    = EXCLUDED.ogtc_twitter_mode;

  RETURN 0;
END;
$$
;

