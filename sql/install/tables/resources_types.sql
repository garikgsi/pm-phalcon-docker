create table if not exists resources_types
(
  type_id smallserial not null
    constraint resources_types_pk
    primary key,
  type_name varchar32,
  type_file_extension char(3),
  type_file_mime varchar32
)
;/*--;--*/

create unique index if not exists resources_types_type_name_uindex
  on resources_types (type_name)
;/*--;--*/

create unique index if not exists resources_types_type_file_extension_uindex
  on resources_types (type_file_extension)
;/*--;--*/

comment on column resources_types.type_name is 'Текстовое название типа'
;/*--;--*/

comment on column resources_types.type_file_extension is 'Расширение файла: css, js, dll, etc.'
;/*--;--*/

comment on column resources_types.type_file_mime is 'MIME тип файла, просто на всякий случай'
;

