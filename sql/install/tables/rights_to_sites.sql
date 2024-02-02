create table if not exists public.rights_to_sites
(
  site_id integer not null
    constraint rights_to_sites_sites_site_id_fk
      references sites,
  right_id integer not null
    constraint rights_to_sites_rights_right_id_fk
      references rights,
  constraint rights_to_sites_pk
    primary key (site_id, right_id)
)
;/*--;--*/

create index if not exists rights_to_sites_right_id_index
  on public.rights_to_sites (right_id)
;/*--;--*/

create index if not exists rights_to_sites_site_id_index
  on public.rights_to_sites (site_id)
;/*--;--*/

