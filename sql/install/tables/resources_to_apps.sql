create table if not exists resources_to_apps
(
  resource_id integer not null
    constraint resources_to_apps_resource_id_fk
    references resources,
  app_id integer not null
    constraint resources_to_apps_app_id_fk
    references apps,
  constraint resources_to_apps_pk
  primary key (resource_id, app_id)
)
;