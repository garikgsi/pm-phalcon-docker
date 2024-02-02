create table if not exists members_auth
(
  member_id bigint not null
    constraint members_auth_member_id_pk
    primary key
    constraint members_auth_member_id_fk
    references members,
  auth_salt varchar64 not null,
  auth_hash hash_md5 not null
)
;
/*--;--*/
