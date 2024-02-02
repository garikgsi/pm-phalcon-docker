create table if not exists public.groups_relationships
(
  group_id integer not null
    constraint groups_relationships_groups_group_id_fk
      references groups,
  parent_id integer not null
    constraint groups_relationships_groups_group_id_fk_2
      references groups,
  constraint groups_relationships_pk
    primary key (group_id, parent_id)
);/*--;--*/