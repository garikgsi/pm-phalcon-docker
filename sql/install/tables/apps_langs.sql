create table if not exists apps_langs
(
  app_id integer not null
    constraint apps_langs_apps_app_id_fk
      references apps,
  lang_id integer not null
    constraint apps_langs_languages_lang_id_fk
      references languages,
  app_title varchar(128),
  app_description text,
  app_slogan varchar(256),
  constraint apps_langs_pk
    primary key (app_id, lang_id)
)
;/*--;--*/



