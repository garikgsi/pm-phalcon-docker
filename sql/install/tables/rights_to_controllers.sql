create table if not exists public.rights_to_controllers
(
  controller_id integer not null
    constraint rights_to_controllers_sites_controllers_controller_id_fk
      references sites_controllers,
  right_id integer not null
    constraint rights_to_controllers_rights_right_id_fk
      references rights,
  constraint rights_to_controllers_pk
    primary key (controller_id, right_id)
)
;/*--;--*/

create index if not exists rights_to_controllers_controller_id_index
  on public.rights_to_controllers (controller_id)
;/*--;--*/

create index if not exists rights_to_controllers_right_id_index
  on public.rights_to_controllers (right_id)
;/*--;--*/
