create table if not exists cnt_seo_langs
(
  seo_id integer not null
    constraint cnt_seo_langs_seo_id_fk
    references cnt_seo,
  lang_id smallint not null
    constraint cnt_seo_langs_lang_id_fk
    references languages,
  seo_meta_title varchar(256),
  seo_meta_keywords varchar(256),
  seo_meta_description varchar(512),
  seo_ogtc_title varchar(256),
  seo_ogtc_description varchar(512),
  constraint cnt_seo_langs_pk
  primary key (seo_id, lang_id)
)
;