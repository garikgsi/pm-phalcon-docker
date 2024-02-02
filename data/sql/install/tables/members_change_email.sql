create table if not exists members_change_email
(
  member_id bigint not null
    constraint members_change_email_pkey
    primary key
    constraint members_change_email_member_id_fk
    references members,
  new_email email not null,
  new_salt bytea not null,
  new_hash bigint not null
)
;
/*--;--*/

create unique index if not exists members_change_email_new_email_uindex
  on members_change_email (new_email)
;
/*--;--*/

