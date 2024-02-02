create table if not exists members_cookies
(
  member_id bigint not null
    constraint members_cookies_member_id_fk
    references members,
  cookie_key hash_md5 not null
    constraint members_cookies_pkey
    primary key,
  cookie_date timestamp default now() not null
)
;
/*--;--*/

