create table if not exists sites
(
  site_id serial not null
    constraint sites_site_id_pk
    primary key,
  site_name varchar32,
  site_lock integer default 0 not null,
  site_mailru_counter varchar100,
  site_yandex_master varchar100,
  site_google_analytics varchar100,
  site_google_publisher varchar100,
  site_routing_default integer default 1 not null,
  site_min_enabled integer default 0 not null,
  site_min_ver_css integer default 0 not null,
  site_min_ver_js integer default 0 not null,
  site_min_ver_templates integer default 0 not null
)
;
/*--;--*/

create unique index if not exists sites_site_name_uindex
  on sites (site_name)
;
/*--;--*/