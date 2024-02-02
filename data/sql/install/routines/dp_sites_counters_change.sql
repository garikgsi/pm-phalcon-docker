create or replace function dp_sites_counters_change(in_site_id integer, in_mailru_counter varchar100, in_yandex_master varchar100, in_google_analytics varchar100, in_google_publisher varchar100) returns smallint
language plpgsql
as $$
begin
  -- Проверка существования сайта
  IF NOT EXISTS (SELECT 1 FROM sites AS s WHERE s.site_id = in_site_id) THEN
    RETURN 1; -- Сайт не существует
  END IF;

  update
    sites
  SET
    site_mailru_counter   = in_mailru_counter,
    site_yandex_master    = in_yandex_master,
    site_google_analytics = in_google_analytics,
    site_google_publisher = in_google_publisher
  WHERE
    site_id = in_site_id;

  return 0;
end;
$$
;

