create table if not exists sites_domains
(
  site_id integer
    constraint sites_domains_site_id_fk
    references sites,
  domain_name varchar100 not null
    constraint sites_domains_pkey
    primary key,
  lang_id smallint
    constraint sites_domains_lang_id_fk
    references languages,
  domain_cross integer default 0 not null
)
;
/*--;--*/

comment on column sites_domains.domain_cross is '0 - кроссдоменная авторизация отключена
1 - кроссдоменная авторизация включена'
;
/*--;--*/

