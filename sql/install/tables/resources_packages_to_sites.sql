create table if not exists resources_packages_to_sites
(
  package_id smallint not null
    constraint resources_packages_to_sites_resources_package_id_fk
    references resources_packages,
  site_id integer not null
    constraint resources_packages_to_sites_site_id_fk
    references sites,
  ordering smallint default 0,
  constraint resources_packages_to_sites_pk
  primary key (package_id, site_id)
)
;/*--;--*/

comment on column resources_packages_to_sites.ordering is 'Сортировка пакетов друг относительно друга внутри сайта'
;

