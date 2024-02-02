create table if not exists members_settings
(
  member_id bigint not null
    constraint members_settings_member_id_fk
    references members,
  sett_id integer not null
    constraint members_settings_sett_id_fk
    references settings,
  value varchar100 not null,
  constraint members_settings_pk
  primary key (member_id, sett_id)
)
;
/*--;--*/

