create table if not exists sites_icons
(
  site_id integer not null
    constraint sites_images_site_id_fk
    references sites,
  icon_name varchar64 not null,
  icon_type char(3) not null,
  icon_width integer,
  icon_height integer,
  constraint sites_images_pk
  primary key (site_id, icon_name)
)
;
/*--;--*/

