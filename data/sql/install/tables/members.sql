create table if not exists members
(
  member_id bigserial not null
    constraint members_pkey
    primary key,
  member_email email not null,
  member_gender gender default 'none'::gender not null,
  member_nick varchar100,
  member_date_register timestamp default now() not null,
  member_nick_lower varchar100 not null,
  member_date_birth date,
  member_date_activity integer,
  lang_id integer default 1 not null
    constraint members_lang_id_fk
    references languages,
  member_group integer
    constraint members_member_group_fk
    references groups
)
;
/*--;--*/

create unique index if not exists members_member_email_uindex
  on members (member_email)
;
/*--;--*/
