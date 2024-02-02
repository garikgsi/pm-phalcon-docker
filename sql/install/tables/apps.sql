create table if not exists apps
(
  app_id serial not null
    constraint apps_pkey
    primary key,
  app_name varchar32,
  app_access char(3) default 0 not null,
  app_all_sites smallint default 1 not null
)
;/*--;--*/

create unique index if not exists apps_app_name_uindex
  on apps (app_name)
;/*--;--*/

comment on column apps.app_access is 'X-- - 0/1 доступно гостям/зареганным
-X- - 0/1 доступно ли специфическим группам
--X - 0/1 доступно ли специфическим пользователям'
;/*--;--*/

comment on column apps.app_all_sites is '0 - доступно только для сайтов из списка apps_access_sites
1 - приложение доступно на всех сайтах'
;

