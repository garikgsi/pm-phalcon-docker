create table if not exists languages
(
  lang_id smallserial not null
    constraint languages_pkey
    primary key,
  lang_name varchar(5) not null,
  lang_title varchar32 not null
)
;
/*--;--*/

create unique index if not exists languages_lang_name_uindex
  on languages (lang_name)
;
/*--;--*/