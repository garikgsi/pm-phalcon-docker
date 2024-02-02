create table if not exists apps_access_sites
(
  app_id integer not null
    constraint apps_access_sites_app_id_fk
    references apps,
  site_id integer not null
    constraint apps_access_sites_site_id_fk
    references sites,
  constraint apps_access_sites_pk
  primary key (app_id, site_id)
)
;