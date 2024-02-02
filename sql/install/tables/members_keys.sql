create table if not exists members_keys
(
  member_id bigint not null
    constraint members_keys_member_id_fk
    references members,
  key_type member_key not null,
  key_value hash_md5,
  key_date timestamp default now(),
  constraint members_keys_primary
  primary key (member_id, key_type)
)
;
/*--;--*/

create unique index if not exists members_keys_key_value_uindex
  on members_keys (key_value)
;
/*--;--*/

