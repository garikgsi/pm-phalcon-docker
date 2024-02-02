create table if not exists public.sites_controllers_langs
(
  controller_id integer not null
    constraint sites_controllers_langs_controller_id_fk
    references sites_controllers,
  lang_id smallint not null
    constraint sites_controllers_langs_lang_id_fk
    references languages,
  controller_title varchar256,
  controller_description text,
  constraint sites_controllers_langs_pk
  primary key (controller_id, lang_id)
)
;
/*--;--*/

