create table if not exists settings_systems
(
  sys_id smallserial not null
    constraint settings_systems_pkey
    primary key,
  sys_app smallint default 0 not null,
  sys_dev smallint default 0 not null,
  sys_order smallint not null,
  sys_name varchar32,
  sys_callback varchar32 not null,
  sys_config json not null,
  sys_title varchar100
)
;
/*--;--*/

create unique index if not exists settings_systems_sys_name_uindex
  on settings_systems (sys_name)
;
/*--;--*/
