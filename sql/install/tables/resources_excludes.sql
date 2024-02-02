create table if not exists resources_excludes
(
  site_id integer not null
    constraint resources_excludes_site_id_fk
    references sites,
  resource_id integer not null
    constraint resources_excludes_resource_id_fk
    references resources,
  constraint resources_excludes_pk
  primary key (site_id, resource_id)
)
;

