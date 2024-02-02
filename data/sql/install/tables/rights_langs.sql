create table if not exists public.rights_langs
(
  right_id integer not null
    constraint rights_langs_rights_right_id_fk
      references rights,
  lang_id integer not null
    constraint rights_langs_languages_lang_id_fk
      references languages,
  right_title varchar(256),
  right_description text,
  constraint rights_langs_pk
    primary key (right_id, lang_id)
)
;/*--;--*/

create index if not exists rights_langs_lang_id_index
  on public.rights_langs (lang_id)
;/*--;--*/

create index if not exists rights_langs_right_id_index
  on public.rights_langs (right_id)
;/*--;--*/

