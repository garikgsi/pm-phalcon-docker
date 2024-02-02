create table if not exists apps_access_members
(
  app_id integer not null
    constraint app_access_members_app_id_fk
    references apps,
  member_id bigint not null
    constraint app_access_members_member_id_fk
    references members,
  constraint app_access_members_pkey
  primary key (app_id, member_id)
)
;