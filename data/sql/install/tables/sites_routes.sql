create table if not exists sites_routes
(
  site_id integer
    constraint sites_routes_site_id_fk
    references sites,
  route_id serial not null
    constraint sites_routes_route_id_pk
    primary key,
  route_order integer,
  route_pattern varchar(512),
  route_json json,
  route_disabled integer default 1 not null
)
;
/*--;--*/

create unique index if not exists sites_routes_unic_route
  on sites_routes (site_id, route_pattern)
;
/*--;--*/