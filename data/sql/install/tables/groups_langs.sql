create table if not exists groups_langs
(
  group_title varchar64,
  group_id integer not null
    constraint groups_langs_group_id_fk
    references groups,
  lang_id integer not null
    constraint groups_langs_lang_id_fk
    references languages,
  constraint groups_langs_pk
  primary key (group_id, lang_id)
)
;
/*--;--*/

