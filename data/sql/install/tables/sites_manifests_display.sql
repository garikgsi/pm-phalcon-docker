create table if not exists sites_manifests_display
(
  display_id serial not null
    constraint sites_manifests_display_display_id_pk
    primary key,
  display_name varchar32
)
;
/*--;--*/

