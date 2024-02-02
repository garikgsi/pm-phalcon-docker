create table if not exists sites_meta
(
  site_id integer not null
    constraint sites_meta_site_id_fk
    references sites,
  lang_id smallint not null
    constraint sites_meta_lang_id_fk
    references languages,
  meta_title varchar100,
  meta_keywords varchar150,
  meta_description varchar150,
  email_name varchar(64),
  email_signature varchar(64),
  constraint sites_meta_pk
  primary key (site_id, lang_id)
)
;
/*--;--*/

