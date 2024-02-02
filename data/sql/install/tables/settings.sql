create table if not exists settings
(
  sett_id serial not null
    constraint settings_pkey
    primary key,
  sett_name varchar32 not null,
  sett_config json not null,
  sett_order smallint not null,
  sett_title varchar100 not null,
  sett_description varchar100,
  sett_info text,
  sys_id smallint not null
    constraint settings_sys_id_fk
    references settings_systems
)
;
/*--;--*/

create unique index if not exists settings_sett_name_uindex
  on settings (sett_name)
;
/*--;--*/

create index if not exists settings_sys_id
  on settings (sys_id)
;
/*--;--*/

