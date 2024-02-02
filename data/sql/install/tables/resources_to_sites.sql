create table if not exists resources_to_sites
(
  resource_id integer not null
    constraint resources_to_sites_resource_id_fk
    references resources,
  site_id integer not null
    constraint resources_to_sites_site_id_fk
    references sites,
  constraint resources_to_sites_pk
  primary key (resource_id, site_id)
)
;

