create table if not exists public.rights_to_apps
(
  app_id integer not null
    constraint rights_to_apps_apps_app_id_fk
      references apps,
  right_id integer not null
    constraint rights_to_apps_rights_right_id_fk
      references rights,
  constraint rights_to_apps_pk
    primary key (app_id, right_id)
)
;/*--;--*/

create index if not exists rights_to_apps_app_id_index
  on public.rights_to_apps (app_id)
;/*--;--*/

create index if not exists rights_to_apps_right_id_index
  on public.rights_to_apps (right_id)
;/*--;--*/