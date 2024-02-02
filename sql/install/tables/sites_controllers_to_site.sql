create table if not exists sites_controllers_to_sites
(
  site_id integer not null
    constraint sites_controllers_to_sites_sites_id_fk
    references sites,
  controller_id integer not null
    constraint sites_controllers_to_sites_controller_id_fk
    references sites_controllers,
  constraint sites_controllers_to_sites_pk
  primary key (site_id, controller_id)
)
;
/*--;--*/

