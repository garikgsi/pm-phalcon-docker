create table if not exists resources_packages
(
  package_id smallserial not null
    constraint resources_packages_pkey
    primary key,
  package_system smallint default 0,
  package_order smallint default 0 not null,
  type_id smallint
    constraint resources_packages_type_id_fk
    references resources_types,
  package_name varchar32 not null,
  package_end smallint default 0,
  package_compress_group integer default 0
)
;/*--;--*/

comment on column resources_packages.package_system is '0 - пакет не является системным и прикрепляется по ссылке
1 - пакет является системным и есть у всех сайтов'
;/*--;--*/

comment on column resources_packages.package_order is 'Используется для сортировки системных пакетов'
;/*--;--*/

comment on column resources_packages.type_id is 'Ссылка на тип прикрепляемых ресурсов'
;/*--;--*/

comment on column resources_packages.package_name is 'Условное название пакета'
;/*--;--*/

comment on column resources_packages.package_end is '0 - пакет идет сначала
1 - пакет идет в самом конце и сортируется по ордеру'
;

