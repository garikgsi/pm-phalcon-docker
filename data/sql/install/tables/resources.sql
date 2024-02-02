create table if not exists resources
(
  resource_id serial not null
    constraint resources_pkey
    primary key,
  type_id smallint
    constraint resources_type_id_fk
    references resources_types,
  resource_order smallint,
  package_id smallint
    constraint resources_package_id_fk
    references resources_packages,
  resource_minified smallint default 0 not null,
  resource_dir varchar256,
  resource_name varchar256
)
;/*--;--*/

comment on column resources.type_id is 'Ссылка на тип файла'
;/*--;--*/

comment on column resources.resource_order is 'Порадковый номер внутри ПАКЕТА'
;/*--;--*/

comment on column resources.package_id is 'Ссылка на пакет, некоторые файлы могут быть без пакетов'
;/*--;--*/

comment on column resources.resource_minified is '0 - файл надо минифицировать
1 - файл уже минифицирован'
;

