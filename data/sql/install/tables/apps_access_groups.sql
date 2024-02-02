create table if not exists apps_access_groups
(
  app_id integer not null
    constraint apps_access_groups_app_id_fk
    references apps,
  group_id integer not null
    constraint apps_access_groups_group_id_fk
    references groups,
  constraint apps_access_groups_pk
  primary key (app_id, group_id)
)
;

