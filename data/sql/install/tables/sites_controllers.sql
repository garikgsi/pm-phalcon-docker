create table if not exists sites_controllers
(
  controller_id serial not null
    constraint site_controllers_pkey
    primary key,
  controller_name varchar128,
  controller_locked integer default 0 not null
)
;
/*--;--*/

create unique index if not exists site_controllers_controller_name_uindex
  on sites_controllers (controller_name)
;
/*--;--*/

