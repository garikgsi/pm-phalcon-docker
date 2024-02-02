create table if not exists members_log_nick
(
  log_id bigserial not null
    constraint members_log_nick_log_id_pk
    primary key,
  member_id bigint not null
    constraint members_nick_log_member_id__fk
    references members,
  nick_old varchar100 not null,
  nick_new varchar100 not null,
  log_date timestamp default now()
)
;
/*--;--*/

create index if not exists members_log_nick_member_id
  on members_log_nick (member_id)
;
/*--;--*/

