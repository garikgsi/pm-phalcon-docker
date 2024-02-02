create table if not exists members_log_auth
(
  log_id bigserial not null
    constraint members_auth_log_pkey
    primary key,
  member_id bigint not null
    constraint members_auth_log_member_id_fk
    references members,
  member_email email not null,
  auth_salt varchar64 not null,
  auth_hash hash_md5 not null,
  log_date timestamp default now()
)
;
/*--;--*/

create index if not exists members_auth_log_member_id
  on members_log_auth (member_id)
;
/*--;--*/

