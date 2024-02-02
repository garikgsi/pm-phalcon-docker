create table if not exists resources_packages_to_apps
(
  package_id smallint not null
    constraint resources_packages_to_apps_package_id_fk
    references resources_packages,
  app_id integer not null
    constraint resources_packages_to_apps_app_id_fk
    references apps,
  ordering smallint default 0,
  constraint resources_packages_to_apps_pk
  primary key (package_id, app_id)
)
;/*--;--*/

comment on column resources_packages_to_apps.ordering is 'Сортировка пакетов друг относительно друга в рамках приложения оверлея'
;

