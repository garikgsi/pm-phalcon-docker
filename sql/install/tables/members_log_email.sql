create table if not exists members_log_email
(
  log_id bigserial not null
    constraint members_log_email_pkey
    primary key,
  member_id bigint not null
    constraint members_log_email_member_id_fk
    references members,
  email_old email not null,
  email_new email not null,
  log_date timestamp default now()
)
;
/*--;--*/

create index if not exists members_log_email_member_id
  on members_log_email (member_id)
;
/*--;--*/

