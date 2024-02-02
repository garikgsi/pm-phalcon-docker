create table if not exists sites_manifests_orientation
(
  orientation_id serial not null
    constraint sites_manifests_orientation_orientation_id_pk
    primary key,
  orientation_name varchar32
)
;
/*--;--*/

